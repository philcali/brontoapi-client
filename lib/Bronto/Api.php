<?php

/**
 * @author     Chris Jones <chris.jones@bronto.com>
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
namespace Bronto;

use \Bronto\Api\Exception as Exception;

class Api
{
    /** URI */
    const BASE_WSDL     = 'https://api.bronto.com/v4?wsdl';
    const BASE_LOCATION = 'https://api.bronto.com/v4';
    const BASE_URL      = 'http://api.bronto.com/v4';

    /**
     * API token
     *
     * @var string
     */
    protected $_token;

    /**
     * @var array
     */
    protected $_options = array(
        // Bronto
        'soap_client'        => '\Bronto\SoapClient',
        'refresh_on_save'    => false,
        'retry_limit'        => 5,
        'debug'              => false,
        'retryer'            => array(
            'type' => null,
            'path' => null,
        ),
        // SoapClient
        'soap_version'       => SOAP_1_1,
        'compression'        => true,
        'encoding'           => 'UTF-8',
        'trace'              => false,
        'exceptions'         => true,
        'cache_wsdl'         => WSDL_CACHE_BOTH,
        'user_agent'         => '\Bronto\Api <https://github.com/leek/bronto_service>',
        'features'           => SOAP_SINGLE_ELEMENT_ARRAYS,
        'connection_timeout' => 30,
    );

    /**
     * Cache of class objects
     *
     * @var array
     */
    protected $_classCache = array();

    /**
     * @var bool
     */
    protected $_connected = false;

    /**
     * @var bool
     */
    protected $_authenticated = false;

    /**
     * @var SoapClient
     */
    protected $_soapClient;

    /**
     * @var Util\Retryer\RetryerInterface
     */
    protected $_retryer;

    /**
     * @var Util\Uuid
     */
    protected $_uuid;

    /**
     * @param null                          $token
     * @param array                         $options
     *
     * @throws Exception
     */
    public function __construct(
        $token = null,
        array $options = array()
    )
    {
        if (!extension_loaded('soap')) {
            throw new Exception('SOAP extension is not loaded.');
        }

        if (!extension_loaded('openssl')) {
            throw new Exception('OpenSSL extension is not loaded.');
        }

        $this->_options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
        $this->_setOptions($options);

        if ($token !== null) {
            $this->setToken($token);
        }

        ini_set('default_socket_timeout', 120);
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function login()
    {
        $token = $this->getToken();
        if (empty($token)) {
            throw new Exception('Token is empty or invalid.', Exception::NO_TOKEN);
        }

        try {
            // Get a new SoapClient
            $this->reset();

            /** @var SoapClient $client */
            $client    = $this->getSoapClient(false);
            $sessionId = $client->login(array('apiToken' => $token))->return;
            $client->__setSoapHeaders(array(
                new \SoapHeader(self::BASE_URL, 'sessionHeader', array('sessionId' => $sessionId))
            ));
            $this->_authenticated = true;
        } catch (Exception $e) {
            $this->throwException($e);
        }

        return $this;
    }

    /**
     * We want all Exceptions to be \Bronto\Api\Exception for request/response
     *
     * @param string|Exception $exception
     * @param null             $message
     * @param null             $code
     *
     * @throws Exception
     */
    public function throwException($exception, $message = null, $code = null)
    {
        if ($exception instanceOf \Exception) {
            if ($exception instanceOf Exception) {
                // Good
            } else {
                // Convert
                /** @var \Bronto\Api\Exception $exception */
                $exception = new Exception($exception->getMessage(), $exception->getCode(), null, $exception);
            }
        } else {
            if (is_string($exception)) {
                if (class_exists($exception, false)) {
                    $exception = new $exception($message, $code);
                } else {
                    $exception = new Exception($exception);
                }
            }
        }

        // For tracking request/response in debug mode
        if ($this->getDebug()) {
            $exception->setRequest($this->getLastRequest());
            $exception->setResponse($this->getLastResponse());
        }

        throw $exception;
    }

    /**
     * Set API token
     *
     * @param $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->reset();
        $this->_token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @return Api\ApiToken\Row
     */
    public function getTokenInfo()
    {
        $apiToken     = $this->getApiTokenObject()->createRow();
        $apiToken->id = $this->getToken();
        $apiToken->read();

        return $apiToken;
    }

    /**
     * @param array $options
     *
     * @return Api
     */
    protected function _setOptions(array $options = array())
    {
        foreach ($options as $name => $value) {
            $this->_setOption($name, $value);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return Api
     */
    protected function _setOption($name, $value)
    {
        if (isset($this->_options[$name])) {
            // Some settings need checked
            switch ($name) {
                case 'soap_client':
                    if (!class_exists($value)) {
                        $this->throwException("Unable to load class: {$value} as SoapClient.");
                    }
                    break;
                case 'soap_version':
                    if (!in_array($value, array(SOAP_1_1, SOAP_1_2))) {
                        $this->throwException('Invalid soap_version value specified. Use SOAP_1_1 or SOAP_1_2 constants.');
                    }
                    break;
                case 'cache_wsdl':
                    if (!in_array($value, array(WSDL_CACHE_NONE, WSDL_CACHE_DISK, WSDL_CACHE_MEMORY, WSDL_CACHE_BOTH))) {
                        $this->throwException('Invalid cache_wsdl value specified.');
                    }
                    // If debug mode, ignore WSDL cache setting
                    if ($this->getDebug()) {
                        $value = WSDL_CACHE_NONE;
                    }
                    break;
                case 'debug':
                    if ($value == true) {
                        $this->_options['trace']      = true;
                        $this->_options['cache_wsdl'] = WSDL_CACHE_NONE;
                    }
                    break;
            }

            $this->_options[$name] = $value;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        if (isset($this->_options[$name])) {
            return $this->_options[$name];
        }

        return $default;
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Account
     */
    public function getAccountObject()
    {
        return $this->getObject('account');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Activity
     */
    public function getActivityObject()
    {
        return $this->getObject('activity');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\ApiToken
     */
    public function getApiTokenObject()
    {
        return $this->getObject('apiToken');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Contact
     */
    public function getContactObject()
    {
        return $this->getObject('contact');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\ContentTag
     */
    public function getContentTagObject()
    {
        return $this->getObject('contentTag');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Conversion
     */
    public function getConversionObject()
    {
        return $this->getObject('conversion');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Delivery
     */
    public function getDeliveryObject()
    {
        return $this->getObject('delivery');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\DeliveryGroup
     */
    public function getDeliveryGroupObject()
    {
        return $this->getObject('deliveryGroup');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Field
     */
    public function getFieldObject()
    {
        return $this->getObject('field');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Message
     */
    public function getMessageObject()
    {
        return $this->getObject('message');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\MessageRule
     */
    public function getMessageRuleObject()
    {
        return $this->getObject('messageRule');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\MailList
     */
    public function getListObject()
    {
        return $this->getObject('mailList');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Login
     */
    public function getLoginObject()
    {
        return $this->getObject('login');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Order
     */
    public function getOrderObject()
    {
        return $this->getObject('order');
    }

    /**
     * Proxy for intellisense
     *
     * @return Api\Segment
     */
    public function getSegmentObject()
    {
        return $this->getObject('segment');
    }

    /**
     * Lazy loads our API objects
     *
     * @param string $object
     *
     * @return Api\Object
     */
    public function getObject($object)
    {
        $object = ucfirst($object);

        if (!isset($this->_classCache[$object])) {
            $className = "\\Bronto\\Api\\{$object}";
            if (class_exists($className)) {
                $this->_classCache[$object] = new $className(array('api' => $this));
            } else {
                $this->throwException("Unable to load class: {$className}");
            }
        }

        return $this->_classCache[$object];
    }

    /**
     * @param bool $authenticate
     *
     * @return SoapClient
     */
    public function getSoapClient($authenticate = true)
    {
        if ($this->_soapClient == null) {
            $this->_connected  = false;
            $soapClientClass   = $this->getOption('soap_client', '\Bronto\SoapClient');
            $this->_soapClient = new $soapClientClass(self::BASE_WSDL, array(
                'soap_version' => $this->_options['soap_version'],
                'compression'  => $this->_options['compression'],
                'encoding'     => $this->_options['encoding'],
                'trace'        => $this->_options['trace'],
                'exceptions'   => $this->_options['exceptions'],
                'cache_wsdl'   => $this->_options['cache_wsdl'],
                'user_agent'   => $this->_options['user_agent'],
                'features'     => $this->_options['features'],
            ));
            $this->_soapClient->__setLocation(self::BASE_LOCATION);
            $this->_connected = true;
            if ($authenticate && !$this->_authenticated && $this->getToken()) {
                $this->login();
            }
        }

        return $this->_soapClient;
    }

    /**
     * @return Api
     */
    public function reset()
    {
        $this->_connected     = false;
        $this->_authenticated = false;
        $this->_soapClient    = null;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return Api
     */
    public function setDebug($value)
    {
        return $this->_setOption('debug', (bool)$value);
    }

    /**
     * @return bool
     */
    public function getDebug()
    {
        return (bool)$this->_options['debug'];
    }

    /**
     * @param array $options
     *
     * @return Util\Retryer\RetryerInterface
     */
    public function getRetryer(array $options = array())
    {
        if (!($this->_retryer instanceOf Util\Retryer\RetryerInterface)) {
            $options = array_merge($this->_options['retryer'], $options);
            switch ($options['type']) {
                case 'file':
                    $this->_retryer = new Util\Retryer\FileRetryer($options);
                    break;
                default:
                    return false;
                    break;
            }
        }

        return $this->_retryer;
    }

    /**
     * @return Util\Uuid
     */
    public function getUuid()
    {
        if (!$this->_uuid) {
            $this->_uuid = new Util\Uuid();
        }

        return $this->_uuid;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return (bool)$this->_connected;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return (bool)$this->_authenticated;
    }

    /**
     * Seamlessly iterate over a rowset.
     *
     * @param Api\Rowset $rowset
     *
     * @return Api\Rowset\Iterator
     */
    public function iterate(Api\Rowset $rowset)
    {
        return new Api\Rowset\Iterator($rowset);
    }

    /**
     * Retrieve request XML
     *
     * @return string
     */
    public function getLastRequest()
    {
        if ($this->_soapClient !== null) {
            return $this->_soapClient->__getLastRequest();
        }

        return '';
    }

    /**
     * Get response XML
     *
     * @return string
     */
    public function getLastResponse()
    {
        if ($this->_soapClient !== null) {
            return $this->_soapClient->__getLastResponse();
        }

        return '';
    }

    /**
     * Retrieve request headers
     *
     * @return string
     */
    public function getLastRequestHeaders()
    {
        if ($this->_soapClient !== null) {
            return $this->_soapClient->__getLastRequestHeaders();
        }

        return '';
    }

    /**
     * Retrieve response headers (as string)
     *
     * @return string
     */
    public function getLastResponseHeaders()
    {
        if ($this->_soapClient !== null) {
            return $this->_soapClient->__getLastResponseHeaders();
        }

        return '';
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return array(
            '_token',
            '_options',
        );
    }
}
