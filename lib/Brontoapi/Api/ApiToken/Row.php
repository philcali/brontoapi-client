<?php

/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * 
 * @property-read string $id
 * @property string $name
 * @property int $permissions
 * @property bool $active
 * @property string $accountId
 * @method \Bronto\Api\ApiToken\Row save() save()
 * @method \Bronto\Api\ApiToken\Row delete() delete()
 * @method \Bronto\Api\ApiToken getApiObject() getApiObject()
 */
namespace Bronto\Api\ApiToken;

class Row extends \Bronto\Api\Row
{
    /**
     * @return $this
     * @throws Exception
     */
    public function read()
    {
        if ($this->id) {
            $params = array('id' => $this->id);
        } elseif ($this->accountId) {
            $params = array('accountId' => $this->accountId);
        } elseif ($this->name) {
            $params = array(
                'name' => array(
                    'value'    => $this->name,
                    'operator' => 'EqualTo',
                )
            );
        } else {
            throw new Exception('Trying to read ApiToken without Id or Name for lookup');
        }

        parent::_read($params);
        return $this;
    }

    /**
     * @return Row
     * @throws Exception
     */
    public function getAccount()
    {
        if (!$this->accountId) {
            if ($this->id || $this->name) {
                $this->read();
            }
            if (!$this->accountId) {
                throw new Exception('No accountId specified to retrieve Account');
            }
        }

        $account = $this->getApi()->getAccountObject()->createRow();
        $account->id = $this->accountId;
        $account->read();

        return $account;
    }

    /**
     * @param int $permissions
     * @return bool
     */
    public function hasPermissions($permissions)
    {
        if ($this->permissions === null) {
            $this->read();
        }

        return $this->permissions >= $permissions;
    }

    /**
     * @return array
     */
    public function getPermissionsLabels()
    {
        return $this->getApiObject()->getPermissionsLabels($this->permissions);
    }
}
