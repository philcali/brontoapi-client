<?php

/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @property-read string $id
 * @property string      $name
 * @property string      $visibility
 * @property int         $deliveryCount
 * @property string      $createdDate
 * @property array       $deliveryIds
 * @property array       $messageRuleIds
 * @property array       $messageIds
 * @method \Bronto\Api\DeliveryGroup getApiObject() getApiObject()
 */
namespace Bronto\Api\DeliveryGroup;

class Row extends \Bronto\Api\Row
{
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
     *
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
            $this->getApi()->throwException($e);
        }

        return $this;
    }
}
