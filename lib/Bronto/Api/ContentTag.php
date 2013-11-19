<?php

/**
 * @author     Jeremy Bobbitt <jeremy.bobbitt@bronto.com>
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @link       http://community.bronto.com/api/v4/objects/general/contenttagobject
 *
 * @method \Bronto\Api\ContentTag\Row createRow() createRow(array $data = array())
 */
namespace Bronto\Api;

class ContentTag extends Object
{
    /**
     * The object name.
     *
     * @var string
     */
    protected $_name = 'ContentTag';

    /**
     * @var array
     */
    protected $_methods = array(
        'addContentTags'    => 'add',
        'readContentTags'   => 'read',
        'updateContentTags' => 'update',
        'deleteContentTags' => 'delete'
    );

    /**
     * @param array $filter
     * @param bool  $includeContent
     * @param int   $pageNumber
     *
     * @return Rowset
     */
    public function readAll(array $filter = array(), $includeContent = true, $pageNumber = 1)
    {
        $params                   = array();
        $params['filter']         = $filter;
        $params['includeContent'] = (bool)$includeContent;
        $params['pageNumber']     = (int)$pageNumber;

        return $this->read($params);
    }
}