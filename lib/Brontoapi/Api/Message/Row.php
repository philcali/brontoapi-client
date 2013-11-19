<?php

/**
 * 
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * 
 * @property-read string $id
 * @property string $name
 * @property string $status
 * @property string $messageFolderId
 * @property array $content
 * @method \Bronto\Api\Message\Row delete() delete()
 * @method \Bronto\Api\Message getApiObject() getApiObject()
 */
namespace Bronto\Api\Message;

class Row extends \Bronto\Api\Row
{
    /**
     * @param $deliveryGroup
     *
     * @return \Bronto\Api\Rowset
     * @throws \Bronto\Api\Exception
     */
    public function addToDeliveryGroup($deliveryGroup)
    {
        if (!$this->id) {
            $exceptionClass = $this->getExceptionClass();
            throw new $exceptionClass("This Message has not been saved yet (has no MessageId)");
        }

        $deliveryGroupId = $deliveryGroup;
        if ($deliveryGroup instanceOf \Bronto\Api\DeliveryGroup\Row) {
            if (!$deliveryGroup->id) {
                $deliveryGroup = $deliveryGroup->read();
            }
            $deliveryGroupId = $deliveryGroup->id;
        }

        $deliveryGroupObject = $this->getApi()->getDeliveryGroupObject();
        return $deliveryGroupObject->addToDeliveryGroup($deliveryGroupId, array(), array($this->id));
    }

    /**
     * @return Row
     */
    public function read()
    {
        if ($this->id) {
            $params = array('id' => $this->id);
        } elseif ($this->name) {
            $params = array(
                'name' => array(
                    'value'    => $this->name,
                    'operator' => 'EqualTo',
                )
            );
        }

        parent::_read($params);
        return $this;
    }

    /**
     * @param bool $upsert
     * @param bool $refresh
     * @return Row
     */
    public function save($upsert = true, $refresh = false)
    {
        if (!$upsert) {
            parent::_save(false, $refresh);
        }

        try {
            parent::_save(true, $refresh);
        } catch (Exception $e) {
            if ($e->getCode() === Exception::MESSAGE_EXISTS) {
                $this->_refresh();
            } else {
                $this->getApi()->throwException($e);
            }
        }

        return $this;
    }
}
