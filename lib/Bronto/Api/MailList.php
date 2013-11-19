<?php

/**
 * @author     Chris Jones <chris.jones@bronto.com>
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @link       http://community.bronto.com/api/v4/objects/general/maillistobject
 *
 * @method \Bronto\Api\MailList\Row createRow() createRow(array $data = array())
 */
namespace Bronto\Api;

class MailList extends Object
{
    /**
     * The object name.
     *
     * @var string
     */
    protected $_name = 'MailList';

    /**
     * @var array
     */
    protected $_methods = array(
        'addLists'    => 'add',
        'readLists'   => 'read',
        'updateLists' => 'update',
        'deleteLists' => 'delete',
        'addToList'   => true,
        'clearLists'  => true,
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

        return $this->read($params);
    }

    /**
     * @param array $data
     *
     * @return Rowset
     */
    public function clear(array $data = array())
    {
        if (array_values($data) !== $data) {
            $data = array($data);
        }

        return $this->doRequest('clearLists', $data);
    }
}
