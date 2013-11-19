<?php
/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
namespace Bronto\Util\Retryer;

interface RetryerInterface
{
    /**
     * @param \Bronto\Api\Object $object
     * @param int $attempts
     * @return string
     */
    function store(\Bronto\Api\Object $object, $attempts = 0);

    /**
     * @param mixed $identifier
     * @return \Bronto\Api\Rowset
     */
    function attempt($identifier);
}
