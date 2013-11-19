<?php

/**
 *
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @property-read string $id
 * @property-read string $name
 * @property-read array  $rules
 * @property-read string $lastUpdated
 * @property-read float  $activeCount
 * @method \Bronto\Api\Segment getApiObject() getApiObject()
 */
namespace Bronto\Api\Segment;

class Row extends \Bronto\Api\Row implements \Bronto\Api\Delivery\Recipient
{
    /**
     * @var bool
     */
    protected $_readOnly = true;

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
     * Required by \Bronto\Api\Delivery\Recipient
     *
     * @return false
     */
    public function isList()
    {
        return false;
    }

    /**
     * Required by \Bronto\Api\Delivery\Recipient
     *
     * @return false
     */
    public function isContact()
    {
        return false;
    }

    /**
     * Required by \Bronto\Api\Delivery\Recipient
     *
     * @return true
     */
    public function isSegment()
    {
        return true;
    }
}
