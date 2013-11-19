<?php

/**
 * @copyright  2011-2013 Bronto Software, Inc.
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * 
 * @property string $username
 * @property string $password
 * @property stdClass $contactInformation
 * @property bool $permissionAgencyAdmin
 * @property bool $permissionAdmin
 * @property bool $permissionApi
 * @property bool $permissionUpgrade
 * @property bool $permissionFatigueOverride
 * @property bool $permissionMessageCompose
 * @property bool $permissionMessageDelete
 * @property bool $permissionAutomatorCompose
 * @property bool $permissionListCreateSend
 * @property bool $permissionListCreate
 * @property bool $permissionSegmentCreate
 * @property bool $permissionFieldCreate
 * @property bool $permissionFieldReorder
 * @property bool $permissionSubscriberCreate
 * @property bool $permissionSubscriberView
 * @method \Bronto\Api\Login\Row read() read()
 * @method \Bronto\Api\Login\Row save() save()
 * @method \Bronto\Api\Login\Row delete() delete()
 * @method \Bronto\Api\Login getApiObject() getApiObject()
 */
namespace Bronto\Api\Login;

class Row extends \Bronto\Api\Row
{
    /**
     * @return ContactInformation
     */
    public function getContactInformation()
    {
        return new ContactInformation($this->contactInformation);
    }
}
