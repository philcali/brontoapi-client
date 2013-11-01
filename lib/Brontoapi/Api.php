<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Brontoapi;

class Api
{
    /**#@+
     * URI
     */
    const BASE_WSDL     = 'https://api.bronto.com/v4?wsdl';
    const BASE_LOCATION = 'https://api.bronto.com/v4';
    const BASE_URL      = 'http://api.bronto.com/v4';
    /**#@-*/

    /**
     * @var \Brontoapi\Client
     */
    protected $_soapClient;

    /**
     * @var string
     */
    protected $_token;

    /**
     * @var array
     */
    protected $_options = array(
        // Bronto
        'soap_client'        => '\Brontoapi\Client',
        'refresh_on_save'    => false,
        'retry_limit'        => 5,
        'debug'              => false,
        'retryer'            => array(
            'type' => null,
            'path' => null,
        ),
        // Client
        'soap_version'       => SOAP_1_1,
        'compression'        => true,
        'encoding'           => 'UTF-8',
        'trace'              => false,
        'exceptions'         => true,
        'cache_wsdl'         => WSDL_CACHE_BOTH,
        'user_agent'         => 'Bronto_Api <https://github.com/leek/bronto_service>',
        'features'           => SOAP_SINGLE_ELEMENT_ARRAYS,
        'connection_timeout' => 30,
    );

    /**
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
     * @var \Brontoapi\Util\Retryer\RetryerInterface
     */
    protected $_retryer;

    /**
     * @var \Brontoapi\Util\Uuid
     */
    protected $_uuid;

    /**
     * Construct Object with Token and Options
     *
     * @param string|null  $token
     * @param array $options
     *
     * @throws \Brontoapi\Api\Exception
     */
    public function __construct($token = null, array $options = array())
    {
        // If PHP Soap extension isn't loaded, throw exception
        if (!extension_loaded('soap')) {
            throw new \Brontoapi\Api\Exception('SOAP extension is not loaded.');
        }

        // If PHP OpenSSL extension isn't loaded, throw exception
        if (!extension_loaded('openssl')) {
            throw new \Brontoapi\Api\Exception('OpenSSL extension is not loaded.');
        }

        // Set Compression and add provided options to defaults
        $this->_options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
        $this->setOptions($options);

        // If Token is provided, set it
        if ($token != null) {
            $this->setToken($token);
        }

        // Override php.ini default_socket_timeout value
        ini_set('default_socket_timeout', 120);
    }

    /**
     * Attempt to login to the API
     *
     * @return $this
     *
     * @throws \Brontoapi\Api\Exception
     */
    public function login()
    {
        $token = $this->getToken();
        if (empty($token)) {
            throw new \Brontoapi\Api\Exception('Token is empty or invalid.', \Brontoapi\Api\Exception::NO_TOKEN);
        }

        try {
            // Get a new Client
            $this->reset();

            /** @var \Brontoapi\Client $client */
            $client    = $this->getSoapClient(false);
            $sessionId = $client->login(array('apiToken' => $token))->return;
            $client->__setSoapHandlers(array(
                new \SoapHeader(self::BASE_URL, 'sessionHeader', array('sessionId' => $sessionId))
            ));
            $this->_authenticated = true;
        } catch (Exception $e) {
            $this->throwException($e);
        }

        return $this;
    }

    /**
     * @param string|Exception $exception
     * @param string           $message
     * @param string           $code
     *
     * @throws \Brontoapi\Api\Exception
     */
    public function throwException($exception, $message = null, $code = null)
    {
        if ($exception instanceOf Exception) {
            if (!$exception instanceOf \Brontoapi\Api\Exception) {
                $exception = new \Brontoapi\Api\Exception($exception->getMessage(), $exception->getCode(), null, $exception);
            }
        } else {
            if (is_string($exception)) {
                if (class_exists($exception, false)) {
                    $exception = new $exception($message, $code);
                } else {
                    $exception = new \Brontoapi\Api\Exception($exception);
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
     * Set Token Param
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
     * Get Token Param
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Get Token Info
     *
     * @return \Brontoapi\Api\ApiToken\Row
     */
    public function getTokenInfo()
    {
        /** @var \Brontoapi\Api\ApiToken\Row $apiToken */
        $apiToken = $this->getApiTokenObject()->createRow();
        $apiToken->id = $this->getToken();
        $apiToken->read();

        return $apiToken;
    }

    /**
     * Set Class Options
     *
     * @param array $options
     *
     * @return $this
     */
    protected function _setOptions(array $options = array())
    {
        foreach ($options as $name => $value) {
            $this->_setOption($name, $value);
        }

        return $this;
    }

    /**
     * Validate Option and Set
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    protected function _setOption($name, $value)
    {
        if (isset($this->_options[$name])) {
            // Some settings need checking
            switch ($name) {
                case 'soap_client':
                    if (!class_exists($value)) {
                        $this->throwException("Unable to load class: {$value} as Client.");
                    }
                    break;
                case 'soap_version':
                    if (!in_array($value, array(SOAP_1_1, SOAP_1_2))) {
                        $this->throwException("Invalid soap_version value specified. Use SOAP_1_1 or SOAP_1_2 constants.");
                    }
                    break;
                case 'cache_wsdl':
                    if (!in_array($value, array(WSDL_CACHE_NONE, WSDL_CACHE_DISK, WSDL_CACHE_MEMORY, WSDL_CACHE_BOTH))) {
                        $this->throwException("Invalid cache_wsdl value specified.");
                    }
                    // If debug mode, ignore WSDL cache setting
                    if ($this->getDebug()) {
                        $value = WSDL_CACHE_NONE;
                    }
                    break;
                case 'debug':
                    if ($value == 'true') {
                        $this->_options['trace']      = true;
                        $this->_options['cache_wsdl'] = WSDL_CACHE_NONE;
                    }
                    break;
            }

            $this->_option[$name] = $value;
        }

        return $this;
    }

    /**
     * Get Option by name or return default
     *
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
     * Get API Account Object
     *
     * @return \Brontoapi\Api\Account
     */
    public function getAccountObject()
    {
        return $this->getObject('account');
    }

    /**
     * Get API Activity Object
     *
     * @return \Brontoapi\Api\Activity
     */
    public function getActivityObject()
    {
        return $this->getObject('activity');
    }

    /**
     * Get API Token Object
     *
     * @return \Brontoapi\Api\ApiToken
     */
    public function getApiTokenObject()
    {
        return $this->getObject('apiToken');
    }

    /**
     * Get API Contact Object
     *
     * @return \Brontoapi\Api\Contact
     */
    public function getContactObject()
    {
        return $this->getObject('contact');
    }

    /**
     * Get API Content Tag Object
     *
     * @return \Brontoapi\Api\ContentTag
     */
    public function getContentTagObject()
    {
        return $this->getObject('contentTag');
    }

    /**
     * Get API Conversion Object
     *
     * @return \Brontoapi\Api\Conversion
     */
    public function getConversionObject()
    {
        return $this->getObject('conversion');
    }

    /**
     * Get API Delivery Object
     *
     * @return \Brontoapi\Api\Delivery
     */
    public function getDeliveryObject()
    {
        return $this->getObject('delivery');
    }

    /**
     * Get API Delivery Group Object
     *
     * @return \Brontoapi\Api\DeliveryGroup
     */
    public function getDeliveryGroupObject()
    {
        return $this->getObject('deliveryGroup');
    }

    /**
     * Get API Field Object
     *
     * @return \Brontoapi\Api\Field
     */
    public function getFieldObject()
    {
        return $this->getObject('field');
    }

    /**
     * Get API Message Object
     *
     * @return \Brontoapi\Api\Message
     */
    public function getMessageObject()
    {
        return $this->getObject('message');
    }

    /**
     * Get API Message Rule Object
     *
     * @return \Brontoapi\Api\MessageRule
     */
    public function getMessageRuleObject()
    {
        return $this->getObject('messageRule');
    }

    /**
     * Get API List Object
     *
     * @return \Brontoapi\Api\List
     */
    public function getListObject()
    {
        return $this->getObject('list');
    }

    /**
     * Get API Login Object
     *
     * @return \Brontoapi\Api\Login
     */
    public function getLoginObject()
    {
        return $this->getObject('login');
    }

    /**
     * Get API Order Object
     *
     * @return \Brontoapi\Api\Order
     */
    public function getOrderObject()
    {
        return $this->getObject('order');
    }

    /**
     * Get API Segment Object
     *
     * @return \Brontoapi\Api\Segment
     */
    public function getSegmentObject()
    {
        return $this->getObject('segment');
    }

    /**
     * Lazy loads our API Objects
     *
     * @param string $object
     *
     * @return \Brontoapi\Api\Object
     */
    public function getObject($object)
    {
        $object = ucfirst($object);

        if (!isset($this->_classCache[$object])) {
            $className = "\\Brontoapi\\Api\\{$object}";
            if (class_exists($className)) {
                $this->_classCache[$object] = new $className(array('api' => $this));
            } else {
                $this->throwException("Unable to load class: {$className}.");
            }
        }

        return $this->_classCache[$object];
    }

    /**
     * Get SOAP Client
     *
     * @param bool $authenticate
     *
     * @return Client
     */
    public function getSoapClient($authenticate = true)
    {
        if ($this->_soapClient == null) {
            $this->_connected = false;
            $soapClientClass = $this->getOption('soap_client', '\Brontoapi\Client');
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
     * Reset Connection Settings
     *
     * @return $this
     */
    public function reset()
    {
        $this->_connected     = false;
        $this->_authenticated = false;
        $this->_soapClient    = false;

        return $this;
    }

    /**
     * Set Debug value
     *
     * @param $value
     *
     * @return $this
     */
    public function setDebug($value)
    {
        return $this->_setOption('debug', (bool) $value);
    }

    /**
     * Get Debug value
     *
     * @return bool
     */
    public function getDebug()
    {
        return (bool) $this->_options['debug'];
    }

    /**
     * @param array $options
     *
     * @return bool|Util\Retryer\FileRetryer
     */
    public function getRetryer(array $options = array())
    {
        if (!($this->_retryer instanceOf \Brontoapi\Util\Retryer\FileRetryer)) {
            $options = array_merge($this->_options['retryer'], $options);
            switch ($options['type']) {
                case 'file':
                    $this->_retryer = new \Brontoapi\Util\Retryer\FileRetryer($options);
                    break;
                default:
                    return false;
                    break;
            }
        }

        return $this->_retryer;
    }

    /**
     * Get Uuid Object
     *
     * @return Util\Uuid
     */
    public function getUuid()
    {
        if (!$this->_uuid) {
            $this->_uuid = new \Brontoapi\Util\Uuid();
        }

        return $this->_uuid;
    }

    /**
     * Determine if Connected
     *
     * @return bool
     */
    public function isConnected()
    {
        return (bool) $this->_connected;
    }

    /**
     * Determine if Authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return (bool) $this->_authenticated;
    }

    /**
     * Iterate over Rowset
     *
     * @param Api\Rowset $rowset
     *
     * @return Api\Rowset\Iterator
     */
    public function iterate(\Brontoapi\Api\Rowset $rowset)
    {
        return new \Brontoapi\Api\Rowset\Iterator($rowset);
    }

    /**
     * Get Last Request
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
     * Get Last Response
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
     * Get Header from Last Request
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
     * Get Header from Last Response
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
     * Put API to sleep
     *
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