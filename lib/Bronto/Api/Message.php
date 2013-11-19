<?php

/**
 * @author     Chris Jones <chris.jones@bronto.com>
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @link       http://community.bronto.com/api/v4/objects/general/messageobject
 *
 * @method \Bronto\Api\Message\Row createRow() createRow(array $data = array())
 */
namespace Bronto\Api;

class Message extends Object
{
    /**
     * @var array
     */
    protected $_methods = array(
        'addMessages'    => 'add',
        'readMessages'   => 'read',
        'updateMessages' => 'update',
        'deleteMessages' => 'delete',
    );

    /**
     * @param array $filter
     * @param bool  $includeContent
     * @param int   $pageNumber
     *
     * @return Rowset
     */
    public function readAll(array $filter = array(), $includeContent = false, $pageNumber = 1)
    {
        $params                   = array();
        $params['filter']         = $filter;
        $params['includeContent'] = (bool)$includeContent;
        $params['pageNumber']     = (int)$pageNumber;

        return $this->read($params);
    }
}
