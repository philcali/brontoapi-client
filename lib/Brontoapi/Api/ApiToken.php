<?php

/**
 * @author     Chris Jones <chris.jones@bronto.com>
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @link       http://community.bronto.com/api/v4/objects/general/apitokenobject
 *
 * @method \Bronto\Api\ApiToken\Row createRow() createRow(array $data)
 */
namespace Bronto\Api;

class ApiToken extends Object
{
    /** Permissions */
    const PERMISSION_NONE  = 0;
    const PERMISSION_READ  = 1;
    const PERMISSION_WRITE = 2;
    const PERMISSION_SEND  = 4;

    /** Permissions Shortcuts */
    const PERMISSIONS_READ_WRITE      = 3;
    const PERMISSIONS_READ_SEND       = 5;
    const PERMISSIONS_WRITE_SEND      = 6;
    const PERMISSIONS_READ_WRITE_SEND = 7;
    const PERMISSIONS_ALL             = self::PERMISSIONS_READ_WRITE_SEND;

    /**
     * @var array
     */
    protected $_options = array(
        'permissions' => array(
            self::PERMISSION_READ,
            self::PERMISSION_WRITE,
            self::PERMISSION_SEND,
            self::PERMISSIONS_READ_WRITE,
            self::PERMISSIONS_READ_SEND,
            self::PERMISSIONS_WRITE_SEND,
            self::PERMISSIONS_READ_WRITE_SEND,
        ),
    );

    /**
     * @var array
     */
    protected $_methods = array(
        'addApiTokens'    => 'add',
        'readApiTokens'   => 'read',
        'updateApiTokens' => 'update',
        'deleteApiTokens' => 'delete',
    );

    /**
     * @param array $filter
     * @param int   $pageNumber
     *
     * @return Rowset
     */
    public function readAll(array $filter = array(), $pageNumber = 1)
    {
        $params               = array();
        $params['filter']     = $filter;
        $params['pageNumber'] = (int)$pageNumber;

        $this->_validateParams($params);

        return parent::read($params);
    }

    /**
     * @param array $params
     *
     * @return bool
     * @throws Exception
     */
    protected function _validateParams(array $params)
    {
        if (!isset($params['filter']) || !is_array($params['filter'])) {
            throw new Exception('readApiTokens requires an filter array.');
        }

        $validFilters   = array('id', 'accountId', 'name');
        $hasValidFilter = false;
        foreach ($params['filter'] as $key => $value) {
            if (in_array($key, $validFilters)) {
                $hasValidFilter = true;
                break;
            }
        }

        if (!$hasValidFilter) {
            throw new Exception('readApiTokens requires a filter by one of: ' . implode(', ', $validFilters));
        }

        return true;
    }

    /**
     * @param int $permissions
     *
     * @return array
     */
    public function getPermissionsLabels($permissions)
    {
        switch ($permissions) {
            case self::PERMISSION_READ:
                return array('read');
                break;
            case self::PERMISSION_WRITE:
                return array('write');
                break;
            case self::PERMISSION_SEND:
                return array('send');
                break;
            case self::PERMISSIONS_READ_WRITE:
                return array('read', 'write');
                break;
            case self::PERMISSIONS_READ_SEND:
                return array('read', 'send');
                break;
            case self::PERMISSIONS_WRITE_SEND:
                return array('write', 'send');
                break;
            case self::PERMISSIONS_READ_WRITE_SEND:
                return array('read', 'write', 'send');
                break;
            case self::PERMISSION_NONE:
                return array('none');
                break;
        }

        return false;
    }
}
