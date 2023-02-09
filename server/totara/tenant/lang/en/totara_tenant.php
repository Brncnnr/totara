<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

$string['allowprelogintenanttheme'] = 'Enable pre-login tenant themes';
$string['allowprelogintenanttheme_desc'] = 'If enabled then "?tenanttheme=tenantidentifier" can be added to any URL to specify tenant theming should be applied before user logs in. Current tenant theme is be also retained after tenant user logs out.';
$string['cannotdisable'] = 'All tenants must be deleted before multitenancy can be disabled.';
$string['categoryname'] = 'Tenant category name';
$string['choosetenant'] = 'Tenant';
$string['choosetenant_help'] = 'Assign users as members in the selected tenant. This setting affects only new users. Existing users won\'t be assigned to the tenant.';
$string['cohortname'] = 'Tenant participants audience name';
$string['csvfile'] = 'CSV file';
$string['csvfilepreview'] = 'CSV file preview';
$string['csvfiletemplate'] = 'CSV file template';
$string['dashboardname'] = 'Tenant dashboard name';
$string['domainmanagers'] = 'Domain managers';
$string['erroridnumberexists'] = 'Tenant with the same identifier already exists';
$string['erroridnumberinvalid'] = 'Invalid tenant identifier, use only lower case letters (a-z) and numbers';
$string['errorinvalidtenant'] = 'Invalid tenant';
$string['errornameexists'] = 'Tenant with the same name already exists';
$string['errornameinvalid'] = 'Invalid tenant name';
$string['errorcsvcolumnsmissing'] = 'Following columns must be included in the CSV file: {$a}';
$string['errorcsvcolumnsextra'] = 'Following unknown columns cannot be present in the CSV file: {$a}';
$string['eventtenantcreated'] = 'Tenant created';
$string['eventtenantdeleted'] = 'Tenant deleted';
$string['eventtenantupdated'] = 'Tenant updated';
$string['loginlink'] = 'Tennant login page';
$string['membercount'] = 'Number of members';
$string['migrationtomemberwarning'] = 'Warning: user account will be migrated to dedicated Tenant account, participation in all other tenants will be terminated.';
$string['migrationtononmemberwarning'] = 'Warning: dedicated tenant user account will be migrated to global user account.';
$string['participant'] = 'Tenant participant';
$string['participantsselect'] = 'To view tenant participants, first <a href="{$a}">select a tenant</a> then click the "Tenant participants" in the administration block.';
$string['participantcount'] = 'Number of participants';
$string['participantmanage'] = 'Manage tenant participation';
$string['participants'] = 'Tenant participants';
$string['participantsreport'] = 'Tenant participants report for non-members';
$string['participantsother'] = 'Non-member participants';
$string['pluginname'] = 'Multitenancy';
$string['settings'] = 'Settings';
$string['suspended'] = 'Suspended';
$string['tenant'] = 'Tenant';
$string['tenantcreate'] = 'Add tenant';
$string['tenantdelete'] = 'Delete tenant';
$string['tenantdeleteconfirm'] = 'Are you sure you want to delete the tenant "{$a->name}"? Any API clients in this tenant will also be deleted.';
$string['tenantdeleteuseraction'] = 'Tenant member status change';
$string['tenantdeleteuserdetele'] = 'Delete all members';
$string['tenantdeleteusersuspend'] = 'Suspend all members';
$string['tenantdeleteusermigrate'] = 'Keep as users without tenant';
$string['tenantidnumber'] = 'Tenant identifier';
$string['tenantidnumber_help'] = 'Tenant identifier must be unique, and include lower case letters (a-z) and numbers (0-9). No capital letters, spaces or special characters. Must start in a letter.';
$string['tenantmember'] = 'Tenant member';
$string['tenantupdate'] = 'Update tenant';
$string['tenants'] = 'Tenants';
$string['tenantsenabled'] = 'Enable multitenancy support';
$string['tenantsenabled_desc'] = 'Enable if you want to create separate self contained tenant instances. Please note that some system features may not be available to tenant users. Report Builder caching is not compatible with multitenancy.

WARNING: Tenant data separation is not guaranteed, see documentation for more information on intended use cases.';
$string['tenantsisolated'] = 'Enable tenant isolation';
$string['tenantsisolated_desc'] = 'Enable if you want to remove all tenant members permissions outside of their tenant contexts.
Tenant isolation is not compatible with some Totara features.

Warning: Do not change this setting when tenant members are logged in.';
$string['tenantsmanage'] = 'Manage tenants';
$string['tenantsuspended'] = 'Tenant suspended';
$string['tenant:config'] = 'Create, update and delete';
$string['tenant:manageparticipants'] = 'Manage tenant participants';
$string['tenant:manageparticipants_help'] = 'Allows the user to manage tenant participants including migrating users to a tenant';
$string['tenant:usercreate'] = 'Create tenant users';
$string['tenant:usercreate_help'] = 'Allows the user to:

* Create new users within a tenant domain
* Import users into a tenant domain if they are permitted to import users also
* Approve user requests for a tenant domain from the auth approved plugin if they are also permitted to approve requests';
$string['tenant:userupload'] = 'Upload tenant users';
$string['tenant:view'] = 'View tenant details';
$string['tenant:view_help'] = 'Allows the user to view the list of tenants and details for each';
$string['tenant:viewparticipants'] = 'View participants in tenant domain';
$string['uploadusers'] = 'Upload users';
$string['usermanagement'] = 'User management';
$string['usermanagers'] = 'User managers';
$string['usersreport'] = 'Tenant users report for members';
$string['useruploadadded'] = 'Users added';
$string['useruploaderrors'] = 'Errors';
$string['useruploaddownloadtemplate'] = 'Download CSV template';
$string['useruploaddownloadtemplate_help'] = 'The template file contains all the possible fields that can be included, not all of these are required.

Required fields are: "username,password,firstname,lastname,email".';
$string['useruploadexistingskipped'] = 'Existing users skipped';
$string['useruploadresultstable'] = 'Results for tenant user upload';
$string['useruploaduserdefaults'] = 'User defaults';
