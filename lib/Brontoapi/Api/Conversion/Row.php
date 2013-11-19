<?php

/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @property-read string  $id
 * @property string       $contactId
 * @property string       $email
 * @property string       $orderId
 * @property string       $item
 * @property string       $description
 * @property int          $quantity
 * @property float        $amount
 * @property float        $orderTotal
 * @property string       $createdDate
 * @property string       $deliveryId
 * @property string       $messageId
 * @property string       $automatorId
 * @property string       $listId
 * @property string       $segmentId
 * @property string       $deliveryType
 * @property-write string $tid
 * @method \Bronto\Api\Conversion getApiObject() getApiObject()
 */
namespace Bronto\Api\Conversion;

class Row extends \Bronto\Api\Row
{
    /**
     * @return Row
     */
    public function read()
    {
        $params = array();

        if ($this->id) {
            $params['id'] = array($this->id);
        } else {
            if ($this->contactId) {
                $params['contactId'] = array($this->contactId);
            }

            if ($this->deliveryId) {
                $params['deliveryId'] = array($this->deliveryId);
            }

            if ($this->orderId) {
                $params['orderId'] = array($this->orderId);
            }
        }

        parent::_read($params);

        return $this;
    }

    /**
     * @param null $upsert
     * @param bool $refresh
     *
     * @return $this|\Bronto\Api\Row
     * @throws \Bronto\Api\Row\Exception
     */
    public function save($upsert = null, $refresh = false)
    {
        /**
         * If the _cleanData array is empty,
         * this is an ADD of a new row.
         * Otherwise it is an UPDATE.
         */
        if (empty($this->_cleanData)) {
            parent::_save(false, $refresh);
        } else {
            throw new \Bronto\Api\Row\Exception(sprintf("Cannot update a %s record.", $this->getApiObject()->getName()));
        }

        return $this;
    }

    /**
     * @return Row
     */
    public function persist()
    {
        return parent::_persist('add', false);
    }

    /**
     * Set row field value
     *
     * @param  string $columnName The column key.
     * @param  mixed  $value      The value for the property.
     */
    public function __set($columnName, $value)
    {
        switch (strtolower($columnName)) {
            case 'email':
                // Trim whitespace
                $value = preg_replace('/\s+/', '', $value);
                // Check if email got truncated
                if (substr($value, -1) === '.') {
                    $value .= 'com';
                }
                break;
        }

        return parent::__set($columnName, $value);
    }
}
