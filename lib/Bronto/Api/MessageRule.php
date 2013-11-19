<?php

/**
 * @author     Chris Jones <chris.jones@bronto.com>
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @link       http://community.bronto.com/api/v4/objects/general/messageruleobject
 *
 * @method \Bronto\Api\MessageRule\Row createRow() createRow(array $data = array())
 */
namespace Bronto\Api;

class MessageRule extends Object
{
    /**
     * The object name.
     *
     * @var string
     */
    protected $_name = 'MessageRule';

    /**
     * @var array
     */
    protected $_methods = array(
        'addMessageRules'    => 'add',
        'readMessageRules'   => 'read',
        'updateMessageRules' => 'update',
        'deleteMessageRules' => 'delete',
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
}
