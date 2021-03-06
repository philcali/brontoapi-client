<?php
/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
namespace Bronto\Api\Conversion;

class Exception extends \Bronto\Api\Exception
{
    const DUPLICATE_ORDER = 901; // Duplicate Order Id: %%id%%.
    const MISSING_AMOUNT = 902; // Missing required field: amount.
    const MISSING_QUANTITY = 903; // Missing required field: quantity.
}
