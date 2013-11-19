<?php

/**
 * @author     Chris Jones <chris.jones@bronto.com>
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 *
 * @link       http://community.bronto.com/api/v4/objects/general/conversionobject
 *
 * @method \Bronto\Api\Conversion\Row createRow() createRow(array $data = array())
 */
namespace Bronto\Api;

class Conversion extends Object
{
    /**
     * @var array
     */
    protected $_methods = array(
        'addConversion'   => 'add',
        'readConversions' => 'read',
    );

    /**
     * @param array $filter
     * @param int   $pageNumber
     *
     * @return Rowset
     */
    public function readAll(array $filter = array(), $pageNumber = 1)
    {
        $params = array(
            'filter'     => array(),
            'pageNumber' => (int)$pageNumber,
        );

        if (!empty($filter)) {
            if (is_array($filter)) {
                $params['filter'] = $filter;
            } else {
                $params['filter'] = array($filter);
            }
        } else {
            $params['filter'] = array('contactId' => array());
        }

        return parent::read($params);
    }
}
