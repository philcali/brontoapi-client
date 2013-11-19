<?php

/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @property-read string $id
 * @property string      $email
 * @property string      $mobileNumber
 * @property string      $status
 * @property string      $msgPref
 * @property string      $source
 * @property string      $customSource
 * @property array       $listIds
 * @property array       $fields
 * @property-read string $created
 * @property-read string $modified
 * @property-read bool   $deleted
 * @property-read int    $numSends
 * @property-read int    $numBounces
 * @property-read int    $numOpens
 * @property-read int    $numClicks
 * @property-read int    $numConversions
 * @property-read float  $conversionAmount
 * @method \Bronto\Api\Contact\Row delete() delete()
 * @method \Bronto\Api\Contact getApiObject() getApiObject()
 */
namespace Bronto\Api\Contact;

class Row extends \Bronto\Api\Row implements \Bronto\Api\Delivery\Recipient
{
    /**
     * @var array
     */
    protected $_data = array(
        'status'          => \Bronto\Api\Contact::STATUS_TRANSACTIONAL,
        'messagePrefence' => \Bronto\Api\Contact::MSGPREF_HTML,
        'source'          => \Bronto\Api\Contact::SOURCE_API,
    );

    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     */
    public function init()
    {
        if (isset($this->_data['fields']) && is_array($this->_data['fields'])) {
            foreach ($this->_data['fields'] as $i => $fieldRow) {
                $this->_data['fields'][$i] = (array)$fieldRow;
            }
            $this->_cleanData = $this->_data;
        }
    }

    /**
     * @return Row
     * @throws Exception
     */
    public function read()
    {
        $params = array();
        if ($this->id) {
            $params = array('id' => $this->id);
        } elseif ($this->email) {
            $params = array(
                'email' => array(
                    'value'    => $this->email,
                    'operator' => 'EqualTo',
                )
            );
        } else {
            throw new Exception('Trying to read Contact without Id or Email for lookup');
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
        try {
            parent::_save($upsert, $refresh);
        } catch (Bronto_Api_Contact_Exception $e) {
            if ($e->getCode() === Exception::ALREADY_EXISTS) {
                $this->_refresh();
            } else {
                $e->appendToMessage("(Email: {$this->email})");
                $this->getApi()->throwException($e);
            }
        }

        return $this;
    }

    /**
     * @return Row
     */
    public function persist()
    {
        return parent::_persist('addOrUpdate', $this->email);
    }

    /**
     * Sets a value for a custom Field
     *
     * @param string|\Bronto\Api\Field\Row $field
     * @param mixed                        $value
     *
     * @return Row
     */
    public function setField($field, $value)
    {
        if ($value === '') {
            return;
        }

        $fieldId = $field;
        if ($field instanceOf Row) {
            if (!$field->id) {
                $field = $field->read();
            }
            $fieldId = $field->id;

            switch ($field->type) {
                case \Bronto\Api\Field::TYPE_DATE:
                    if ($value instanceOf \DateTime) {
                        $value = date('c', $value->getTimestamp());
                    } else {
                        $value = date('c', strtotime($value));
                    }
                    break;
                case \Bronto\Api\Field::TYPE_INTEGER:
                    $value = (int)$value;
                    break;
                case \Bronto\Api\Field::TYPE_FLOAT:
                    $value = (float)$value;
                    break;
            }
        }

        $field = array(
            'fieldId' => $fieldId,
            'content' => $value,
        );

        if (!isset($this->_data['fields']) || !is_array($this->_data['fields'])) {
            $this->_data['fields'] = array();
        } else {
            // Check for dupes
            foreach ($this->_data['fields'] as $i => $_field) {
                if ($_field['fieldId'] == $field['fieldId']) {
                    $this->_data['fields'][$i]       = $field;
                    $this->_modifiedFields['fields'] = true;

                    return $this;
                }
            }
        }

        $this->_data['fields'][]         = $field;
        $this->_modifiedFields['fields'] = true;

        return $this;
    }

    /**
     * Retrieves a value for a custom field
     * NOTE: Loads the field for you if it hasn't been requested
     *
     * @param string|\Bronto\Api\Field\Row $field $field
     *
     * @return mixed
     */
    public function getField($field)
    {
        $fieldId = $field;
        if ($field instanceOf \Bronto\Api\Field\Row) {
            if (!$field->id) {
                $field = $field->read();
            }
            $fieldId = $field->id;
        }

        // Determine if we have the field already
        if (isset($this->_data['fields']) && is_array($this->_data['fields'])) {
            foreach ($this->_data['fields'] as $i => $fieldRow) {
                if ($fieldRow['fieldId'] == $fieldId) {
                    return $fieldRow['content'];
                }
            }
        }

        // We don't, so request it
        if ($this->id) {
            try {
                if ($rowset = $this->getApiObject()->readAll(array('id' => $this->id), array($fieldId))) {
                    foreach ($rowset as $row) {
                        $data = $row->getData();
                        if (is_array($data) && !empty($data) && isset($data['fields'])) {
                            $this->_data['fields'] = array_merge(
                                isset($this->_data['fields']) ? $this->_data['fields'] : array(),
                                $data['fields']
                            );
                            $this->_cleanData      = $this->_data;
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                return false;
            }
        }

        // Try the traverse again
        if (isset($this->_data['fields']) && is_array($this->_data['fields'])) {
            foreach ($this->_data['fields'] as $i => $fieldRow) {
                if ($fieldRow['fieldId'] == $fieldId) {
                    return $fieldRow['content'];
                }
            }
        }

        // Something went horribly wrong
        return null;
    }

    /**
     * @return array
     */
    public function getLists()
    {
        if ($this->id) {
            $filter = array('id' => $this->id);
        } else {
            $filter = array(
                'email' => array(
                    'value'    => $this->email,
                    'operator' => 'EqualTo',
                )
            );
        }

        try {
            $rowset = $this->getApiObject()->readAll($filter, array(), true);

            if ($rowset->count() > 0) {
                $data = $rowset->current()->getData();
                if (isset($data['listIds'])) {
                    return $data['listIds'];
                }
            }
        } catch (Exception $e) {
            // Ignore
        }

        return array();
    }

    /**
     * @param \Bronto\Api\MailList\Row|string $list
     *
     * @return Row
     */
    public function addToList($list)
    {
        $listId = $list;
        if ($list instanceOf \Bronto\Api\MailList\Row) {
            if (!$list->id) {
                $list = $list->read();
            }
            $listId = $list->id;
        }

        if (!isset($this->_data['listIds'])) {
            $this->_loadLists();
        }

        if (!in_array($listId, $this->_data['listIds'])) {
            $this->_data['listIds'][]         = $listId;
            $this->_modifiedFields['listIds'] = true;
        }

        return $this;
    }

    /**
     * @param \Bronto\Api\MailList\Row|string $list
     *
     * @return Row
     */
    public function removeFromList($list)
    {
        $listId = $list;
        if ($list instanceOf \Bronto\Api\MailList\Row) {
            if (!$list->id) {
                $list = $list->read();
            }
            $listId = $list->id;
        }

        if (!isset($this->_data['listIds'])) {
            $this->_loadLists();
        }

        if (is_array($this->_data['listIds'])) {
            foreach ($this->_data['listIds'] as $i => $id) {
                if ($id == $listId) {
                    unset($this->_data['listIds'][$i]);
                    break;
                }
            }
        }

        $this->_modifiedFields['listIds'] = true;

        return $this;
    }

    /**
     * @return void
     */
    protected function _loadLists()
    {
        if (!isset($this->_data['listIds'])) {
            $this->_data['listIds'] = array();
        }

        $listIds = $this->getLists();
        foreach ($listIds as $listId) {
            $this->_data['listIds'][]         = $listId;
            $this->_modifiedFields['listIds'] = true;
        }
    }

    /**
     * @param array $additionalFilter
     * @param int   $pageNumber
     *
     * @return \Bronto\Api\Rowset
     * @throws \Bronto\Api\Exception
     */
    public function getDeliveries(array $additionalFilter = array(), $pageNumber = 1)
    {
        if (!$this->id) {
            $exceptionClass = $this->getExceptionClass();
            throw new $exceptionClass("This Contact has not been retrieved yet (has no ContactId)");
        }

        /* @var $deliveryObject \Bronto\Api\Delivery */
        $deliveryObject = $this->getApi()->getDeliveryObject();
        $filter         = array_merge_recursive(array('contactId' => $this->id), $additionalFilter);

        return $deliveryObject->readDeliveryRecipients($filter, $pageNumber);
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
     * @return true
     */
    public function isContact()
    {
        return true;
    }

    /**
     * Required by \Bronto\Api\Delivery\Recipient
     *
     * @return false
     */
    public function isSegment()
    {
        return false;
    }
}
