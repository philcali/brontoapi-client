<?php

/**
 *
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @property-read id
 * @property name
 * @property type
 * @property messagedId
 * @method \Bronto\Api\MessageRule\Row delete() delete()
 * @method \Bronto\Api\MessageRule getApiObject() getApiObject()
 */
namespace Bronto\Api\MessageRule;

class Row extends \Bronto\Api\Row
{
    /**
     * @return Row
     */
    public function read()
    {
        if ($this->id) {
            $params = array('id' => $this->id);
        } else {
            $params = array(
                'name' => array(
                    'value'    => $this->name,
                    'operator' => 'EqualTo',
                )
            );
        }

        return parent::_read($params);
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
            if ($e->getCode() === Exception::AUTOMATOR_EXISTS) {
                $this->_refresh();
            } else {
                $this->getApi()->throwException($e);
            }
        }

        return $this;
    }
}
