<?php

/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * 
 * @property-read string $activityDate
 * @property-read string $contactId
 * @property-read string $deliveryId
 * @property-read string $messageId
 * @property-read string $listId
 * @property-read string $segmentId
 * @property-read string $trackingType
 * @property-read string $bounceReason
 * @property-read string $bounceType
 * @property-read string $linkName
 * @property-read string $linkUrl
 * @method bool isOpen() isOpen()
 * @method bool isClick() isClick()
 * @method bool isConversion() isConversion()
 * @method bool isBounce() isBounce()
 * @method bool isSend() isSend()
 * @method bool isUnsubscribe() isUnsubscribe()
 * @method bool isView() isView()
 * @method \Bronto\Api\Contact\Row getContact() getContact()
 * @method \Bronto\Api\Delivery\Row getDelivery() getDelivery()
 * @method \Bronto\Api\Message\Row getMessage() getMessage()
 * @method \Bronto\Api\MailList\Row getList() getList()
 * @method \Bronto\Api\Activity getApiObject() getApiObject()
 */
namespace Bronto\Api\Activity;

class Row extends \Bronto\Api\Row
{
    /**
     * Tracks columns that are dates.
     *
     * @var array
     */
    protected $_dateFields = array(
        'activityDate' => true
    );

    /**
     * @var bool
     */
    protected $_readOnly = true;

    /**
     * @param $name
     * @param $arguments
     *
     * @return bool|\Bronto\Api\Row
     * @throws \BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        // Check is{Type}
        if (substr($name, 0, 2) == 'is') {
            $type = strtolower(substr($name, 2));
            if ($this->getApiObject()->isValidOptionValue('trackingType', $type)) {
                return $this->trackingType == $type;
            }
        }

        // Check get{Object}
        if (substr($name, 0, 3) == 'get') {
            $object = strtolower(substr($name, 3));
            switch ($object) {
                case 'contact':
                case 'delivery':
                case 'message':
                case 'list':
                    // Cache object result
                    $cacheObject = (bool) (isset($arguments[0]) && $arguments[0]);
                    $idField     = "{$object}Id";
                    if (isset($this->{$idField}) && !empty($this->{$idField})) {
                        if ($cacheObject) {
                            $cached = $this->getApiObject()->getFromCache($object, $this->{$idField});
                            if ($cached) {
                                return $cached;
                            }
                        }
                        $apiObject = $this->getApi()->getObject($object);
                        $row       = $apiObject->createRow();
                        $row->id   = $this->{$idField};
                        $row->read();
                        if ($cacheObject) {
                            $this->getApiObject()->addToCache($object, $this->{$idField}, $row);
                        }
                        return $row;
                    } else {
                        return false;
                    }
                    break;
            }
        }

        throw new \BadMethodCallException("The method {$name} does not exist");
    }

    /**
     * @return boolean
     */
    public function isSoftBounce()
    {
        if ($this->isBounce()) {
            return $this->getApiObject()->isBounceSoft($this->bounceType);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHardBounce()
    {
        if ($this->isBounce()) {
            return $this->getApiObject()->isBounceHard($this->bounceType);
        }

        return false;
    }

    /**
     * @param null $upsert
     * @param null $refresh
     *
     * @return \Bronto\Api\Row|void
     * @throws Exception
     */
    public function save($upsert = null, $refresh = null)
    {
        throw new Exception('You cannot create/update an Activity row.');
    }

    /**
     * @return \Bronto\Api\Row|void
     * @throws Exception
     */
    public function delete()
    {
        throw new Exception('You cannot delete an Activity row.');
    }
}
