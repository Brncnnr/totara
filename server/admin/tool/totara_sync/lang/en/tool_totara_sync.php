<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */
$string['pluginname'] = 'HR Import';

$string['sync'] = 'Import';
$string['totarasync'] = 'HR Import';
$string['totarasync_help'] = 'Enabling HR Import will cause the element to be updated/deleted from an external source (if configured). The idnumber field MUST have a value to enable this field.
See the HR Import settings in the Administration menu.';
$string['totara_sync:manage'] = 'Manage HR Import';
$string['totara_sync:runsync'] = 'Run HR Import via the web interface';
$string['totara_sync:setfileaccess'] = 'Set HR Import file access';
$string['totara_sync:manageuser'] = 'Manage HR Import users';
$string['totara_sync:manageorg'] = 'Manage HR Import organisations';
$string['totara_sync:managepos'] = 'Manage HR Import positions';
$string['totara_sync:managejobassignment'] = 'Manage HR Import job assignments';
$string['totara_sync:managecomp'] = 'Manage HR Import competencies';
$string['totara_sync:uploaduser'] = 'Upload HR Import users';
$string['totara_sync:uploadorg'] = 'Upload HR Import organisations';
$string['totara_sync:uploadpos'] = 'Upload HR Import positions';
$string['totara_sync:uploadcomp'] = 'Upload HR Import competencies';
$string['totara_sync:uploadjobassignment'] = 'Upload HR Import job assignments';
$string['totara_sync:deletesynclog'] = 'Clear the HR Import Logs';
$string['settingssaved'] = 'Settings saved';
$string['settingssavedlinktosource'] = 'Settings updated. The source settings for this element can be <a href=\'{$a}\'>configured here</a>.';
$string['elementenabled'] = 'Element enabled';
$string['elementdisabled'] = 'Element disabled';
$string['uploadsuccess'] = 'HR Import files uploaded successfully';
$string['uploaderror'] = 'The was a problem with uploading the file(s)...';
$string['uploadaccessdenied'] = 'Your HR Import configuration is set to look for files in a server directory, not to use uploaded files. To change this go {$a}';
$string['uploadaccessdeniedlink'] = 'here';
$string['couldnotmakedirsforx'] = 'Could not make necessary directories for {$a}';
$string['note:syncfilepending'] = 'NOTE: A pending HR Import file exists. Uploading another file now will overwrite the pending one.';
//
// Elements
//
$string['element'] = 'Element';
$string['elements'] = 'Elements';
$string['elementnotfound'] = 'Element not found';
$string['manageelements'] = 'Manage elements';
$string['managesyncelements'] = 'Manage HR Import elements';
$string['noenabledelements'] = 'No enabled elements';
$string['elementxnotfound'] = 'Element {$a} not found';
$string['notadirerror'] = 'Directory \'{$a}\' does not exist or not accessible';
$string['readonlyerror'] = 'Directory \'{$a}\' is read-only';
$string['pathformerror'] = 'Path not found';
$string['elementsusingdefault'] = 'Elements using default settings';
$string['noneusedefault'] = 'None';
$string['compsynctask'] = 'Competency HR Import';
$string['jobassignmentsynctask'] = 'Job assignment HR Import';
$string['orgsynctask'] = 'Organisation HR Import';
$string['possynctask'] = 'Position HR Import';
$string['usersynctask'] = 'User HR Import';

// Hierarchy items
$string['displayname:org'] = 'Organisation';
$string['settings:org'] = 'Organisation element settings';
$string['displayname:pos'] = 'Position';
$string['settings:pos'] = 'Position element settings';
$string['displayname:comp'] = 'Competency';
$string['settings:comp'] = 'Competency element settings';
$string['removeitems'] = 'Remove items';
$string['removeitemsdesc'] = 'Specify what to do with internal items during HR Import when item was removed from source.';

// User
$string['displayname:user'] = 'User';
$string['settings:user'] = 'User element settings';
$string['deleted'] = 'Deleted';
$string['sourceallrecords'] = 'Source contains all records';
$string['sourceallrecordsdesc'] = 'Does the source provide all HR Import records, everytime <strong>OR</strong> are only records that need to be updated/deleted provided? If "No" (only records to be updated/deleted), then the source must use the <strong>"delete" flag</strong>.';
$string['allowduplicatedemails'] = 'Allow duplicate emails';
$string['allowduplicatedemailsdesc'] = 'If "Yes" duplicated emails are allowed from the source. If "No" only unique emails are allowed.';
$string['defaultemailaddress'] = 'Default Email Address';
$string['emailsettingsdesc'] = 'If duplicate emails are allowed you can set a default email address that will be used when creating/updating users with a blank or invalid email. If duplicates are not allowed every user must have a unique email, if they do not they will be skipped.';
$string['ignoreexistingpass'] = 'Only import new users\' passwords';
$string['ignoreexistingpassdesc'] = 'If "Yes" passwords are only updated for new users, if "No" all users\' passwords are updated';
$string['forcepwchange'] = 'Force password change for new users';
$string['forcepwchangedesc'] = 'If "yes" new users have their password set but are forced to change it on first login. <br /><strong>Note:</strong> Users with generated passwords will be forced to change them on first login regardless of this configuration option.';
$string['undeletepwreset'] = 'Reset passwords for undeleted users';
$string['undeletepwresetdesc'] = 'If "yes" and if a password is not specified in the import then undeleted users will have their passwords reset, will receive an email with the new password and will be forced to reset their password when first logging in';
$string['checkconfig'] = 'These settings change the expected <a href=\'{$a}\'>source configuration</a>. You should check the format of your data source matches the new source configuration';
$string['allowedactions'] = 'Allowed HR Import actions';
$string['create'] = 'Create';
$string['delete'] = 'Delete';
$string['delete_help'] = 'Default: Keep internal user.
<br>
If set to \'Suspend internal user\', the \'suspended\' column in the source file will be ignored, and 
all users that require suspension must either have their \'deleted\' column set to \'1\' (if source does not contain all records) 
or not be present in the file (if source contains all records).';
$string['keep'] = 'Keep';
$string['update'] = 'Update';

// Job assignment
$string['displayname:jobassignment'] = 'Job assignment';
$string['settings:jobassignment'] = 'Job assignment element settings';
$string['previouslylinkedmismatch'] = '<strong>Warning:</strong> Import set to update job assignment ID numbers, but previous import has been completed with that setting off. This indicates a problem with your HR Import configuration, please contact your site administrator.';
$string['updateidnumbers'] = 'Update ID numbers';
$string['updateidnumbersdesc'] = 'If set to \'Yes\', only one job assignment record can be provided in the import for each user, and this will be applied to the user\'s first job assignment (where sort order equals 1) and the ID number will be updated.<br>
If set to \'No\', imported data will be applied to any existing job assignments where the id number matches.<br>
<br>
Note: The first time an import is performed with this option set to \'No\', this will become permanently set and the setting will no longer appear in this form.';

///
/// Sources
///
$string['source'] = 'Source';
$string['sources'] = 'Sources';
$string['sourcenotfound'] = 'Source for \'{$a}\' not found';
$string['sourcesettings'] = 'Source settings';
$string['configurefileaccess'] = 'File access settings must be configured before you can import data.';
$string['configuresource'] = 'Configure source';
$string['nosources'] = 'No sources';
$string['filedetails'] = 'File details';
$string['nameandloc'] = 'Name and location';
$string['fieldmappings'] = 'Field mappings';
$string['uploadsyncfiles'] = 'Upload HR Import files';
$string['sourcedoesnotusefiles'] = 'Source does not use files';
$string['nosourceconfig'] = 'No source configuration for \'{$a}\'';
$string['sourceconfigured'] = 'Source has configuration';
$string['sourcetypenotloaded'] = 'Source could not be loaded for {$a}';
$string['uploadfilelink'] = 'Files can be uploaded <a href=\'{$a}\'>here</a>';

// Hierarchy items
$string['customfieldfullnamewithtype'] = '{$a->customfield_fullname} ({$a->type_fullname})';
$string['customfieldshortnamewithtype'] = '{$a->customfield_shortname} ({$a->type_idnumber})';
$string['displayname:totara_sync_source_org_csv'] = 'CSV';
$string['displayname:totara_sync_source_org_database'] = 'External Database';
$string['displayname:totara_sync_source_pos_csv'] = 'CSV';
$string['displayname:totara_sync_source_pos_database'] = 'External Database';
$string['displayname:totara_sync_source_comp_csv'] = 'CSV';
$string['displayname:totara_sync_source_comp_database'] = 'External Database';
$string['hierarchycustomfieldneedstypeid'] = 'Type must be imported when importing custom fields';
$string['settings:totara_sync_source_org_csv'] = 'Organisation - CSV source settings';
$string['settings:totara_sync_source_org_database'] = 'Organisation - external database source settings';
$string['settings:totara_sync_source_pos_csv'] = 'Position - CSV source settings';
$string['settings:totara_sync_source_pos_database'] = 'Position - external database source settings';
$string['settings:totara_sync_source_comp_csv'] = 'Competency - CSV source settings';
$string['settings:totara_sync_source_comp_database'] = 'Competency - external database source settings';

// User
$string['displayname:totara_sync_source_user_csv'] = 'CSV';
$string['displayname:totara_sync_source_user_database'] = 'External Database';
$string['settings:totara_sync_source_user_csv'] = 'User - CSV source settings';
$string['settings:totara_sync_source_user_database'] = 'User - external database source settings';
$string['importfields'] = 'Fields to import';
$string['firstname'] = 'First name';
$string['lastname'] = 'Last name';
$string['firstnamephonetic'] = 'First name phonetic';
$string['lastnamephonetic'] = 'Last name Phonetic';
$string['middlename'] = 'Middle name';
$string['alternatename'] = 'Alternate name';
$string['email'] = 'Email';
$string['city'] = 'City';
$string['country'] = 'Country';
$string['csvemptysettingdeleteinfo'] = 'The use of empty fields in your CSV file will delete the field\'s value in your site.';
$string['csvemptysettingkeepinfo'] = 'The use of empty fields in your CSV file will leave the field\'s current value in your site.';
$string['timezone'] = 'Timezone';
$string['lang'] = 'Language';
$string['description'] = 'Description';
$string['url'] = 'URL';
$string['institution'] = 'Institution';
$string['department'] = 'Department';
$string['phone1'] = 'Phone 1';
$string['phone2'] = 'Phone 2';
$string['address'] = 'Address';
$string['orgidnumber'] = 'Organisation';
$string['posidnumber'] = 'Position';
$string['manageridnumber'] = 'Manager';
$string['managerjaidnumber'] = 'Manager\'s job assignment';
$string['appraiseridnumber'] = 'Appraiser';
$string['auth'] = 'Auth';
$string['password'] = 'Password';
$string['suspendcolumndisabled'] = 'The \'suspended\' column in your CSV file will be ignored while the \'Delete\' action is set to \'Suspend internal user\'. 
<br>
All users that require suspension must either have their \'deleted\' column set to \'1\' (if source does not contain all records) or not be present in the file (if source contains all records). 
Go to the User element settings and change the \'Delete\' action if you wish to include the "suspended" column in the import.';
$string['suspended'] = 'Suspended';
$string['emailstop'] = 'Turn email off';
$string['customfields'] = 'Custom fields';
$string['csvimportfilestructinfo'] = 'The current config requires a CSV file with the following structure:<br><pre>{$a}<br>...<br>...<br>...</pre>';
$string['tempmanageridnumber'] = 'Temporary Manager';

// Organisation
$string['shortname'] = 'Shortname';
$string['parentidnumber'] = 'Parent';
$string['typeidnumber'] = 'Type';

// Competency
$string['aggregationmethod'] = 'Aggregation method';
$string['unrecognisedaggregrationmethod'] = 'Unrecognised aggregation method value: {$a}';
$string['aggregrationmethodmusthavevalue'] = 'The aggregation method must be given a value for item with idnumber: {$a}';
$string['assignavailability'] = 'Assignment creation availability';
$string['unrecognisedassignavailability'] = 'Unrecognised assignment creation availability value: {$a}';

// Job assignment
$string['appraiserxnotexistjobassignment'] = 'User \'{$a->appraiseridnumber}\' does not exist and was set to be assigned as appraiser. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['createdjobassignmentx'] = 'Created job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['deletedjobassignmentx'] = 'Deleted job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['displayname:totara_sync_source_jobassignment_csv'] = 'CSV';
$string['displayname:totara_sync_source_jobassignment_database'] = 'External Database';
$string['duplicateentriesjobassignment'] = 'Multiple entries found for job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'. No updates made to this job assignment.';
$string['emptymanagerjobassignmentidnumber'] = 'Missing manager\'s job assignment id number for assigning manager \'{$a->manageridnumber}\'. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['enddate'] = 'End date';
$string['fullname'] = 'Full name';
$string['invaliddateformatjobassignment'] = 'Invalid date format for field \'{$a->field}\' for job assignment with id number \'{$a->idnumber}\' for user \'{$a->useridnumber}\'. Values for this field will not be added/updated.';
$string['jobassignmentsyncdisabled'] = 'Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\' as the HR Import setting for that job assignment is disabled.';
$string['managementloop'] = 'Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\' as creating it would generate a circular management structure.';
$string['manager'] = 'Manager';
$string['managerxhasnojobassignment'] = 'User \'{$a->manageridnumber}\' does not have a job assignment and was set to be assigned as manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['managerjaxnotexistjobassignment'] = 'Job assignment \'{$a->managerjaidnumber}\' for manager \'{$a->manageridnumber}\' does not exist. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['managerxnotexistjobassignment'] = 'User \'{$a->manageridnumber}\' does not exist and was set to be assigned as manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['missingrequiredfieldjobassignment'] = 'Some records are missing their idnumber and/or useridnumber. These records were skipped.';
$string['multiplejobsdisablednocreate'] ='Tried to create a job assignment but multiple job assignments site setting is disabled and a job assignment already exists. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['orgxnotexistjobassignment'] = 'Organisation \'{$a->orgidnumber}\' does not exist. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['posxnotexistjobassignment'] = 'Position \'{$a->posidnumber}\' does not exist. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['selfassignedmanagerjobassignment'] = 'User \'{$a->useridnumber}\' cannot be their own manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['selfassignedtempmanagerjobassignment'] = 'User \'{$a->useridnumber}\' cannot be their own temporary manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['selfassignedappraiserjobassignment'] = 'User \'{$a->useridnumber}\' cannot be their own appraiser. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['settings:totara_sync_source_jobassignment_csv'] = 'Job assignment - CSV source settings';
$string['settings:totara_sync_source_jobassignment_database'] = 'Job assignment - external database source settings';
$string['startdate'] = 'Start date';
$string['startafterendjobassignment'] = 'Start date cannot be later than end date. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['unabletomatchuseridnumber'] = 'Unable to match useridnumber \'{$a->useridnumber}\' to a user ID number for job assignment \'{$a->idnumber}\'';
$string['updatedjobassignmentx'] = 'Updated job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['tempmanagerxnotexistjobassignment'] = 'User \'{$a->tempmanageridnumber}\' does not exist and was set to be assigned as temporary manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['willcreateduplicatejobidnumber'] = 'User \'{$a->useridnumber}\' has another job assignment with the same idnumber as what is being updated. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['tempemptymanagerjobassignmentidnumber'] = 'Missing temporary manager\'s job assignment id number for assigning manager \'{$a->tempmanageridnumber}\'. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['tempmanagerjaxnotexistjobassignment'] = 'Job assignment \'{$a->tempmanagerjaidnumber}\' for temporary manager \'{$a->tempmanageridnumber}\' does not exist. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['tempmanagerxexpirydatenotset'] = 'The expiry date is not set for assigning \'{$a->tempmanageridnumber}\' as temporary manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['tempmanagerxexpirydateinthepast'] = 'The expiry date can not be in the past for assigning \'{$a->tempmanageridnumber}\' as temporary manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';
$string['tempmanagerxhasnojobassignment'] = 'User \'{$a->tempmanageridnumber}\' does not have a job assignment and was set to be assigned as temporary manager. Skipped job assignment \'{$a->idnumber}\' for user \'{$a->useridnumber}\'.';

// Multitenancy.
$string['tenantmember'] = 'Tenant member';
$string['tenantmember_help'] = 'The tenant idnumber for the tenant the user should be a member of.

NOTE: A user can only be set as a member or a participant of a tenant, not both.';
$string['tenantmemberxnotexist'] = 'Tenant idnumber {$a->tenantmember} does not exist. Skipped user {$a->idnumber}';
$string['tenantmemberandparticipantpresent'] = 'Only a tenant member or participant can be set, not both, for user {$a->idnumber}';
$string['tenantparticipant'] = 'Tenant participant';
$string['tenantparticipant_help'] = 'A comma separated list of tenant idnumbers for the tenants the user should be a participant of.

NOTE: A user can only be set as a member or a participant of a tenant, not both.';
$string['tenantparticipantidnumberxnotexist'] = 'Tenant participant idnumber {$a->tenantmember} does not exist. Skipped user {$a->idnumber}';
$string['tenantparticipantidnumbersxnotexist'] = 'Tenant participants idnumbers {$a->tenantmembers} do not exist. Skipped user {$a->idnumber}';
$string['tenantuserxproblem'] = 'User {$a->idnumber} tenant related update failed';

// Database sources
$string['dbtype'] = 'Database type';
$string['dbname'] = 'Database name';
$string['dbuser'] = 'Database user';
$string['dbuser_help'] = 'Most database types require a valid database username in order to establish connection. This does not apply to Microsoft SQL server, which can use other authentication methods.';
$string['dbpass'] = 'Database password';
$string['dbhost'] = 'Database hostname';
$string['dbport'] = 'Database port';
$string['dbtable'] = 'Database table';
$string['dbdateformat'] = 'Date format';
$string['dbdateformat_help'] = 'Used to specify the date format for the database table columns that contain dates.';
$string['databaseconnectfail'] = 'Failed to connect to database';
$string['cannotconnectdbsettings'] = 'Cannot connect to database, please check settings';
$string['dbmissingcolumnx'] = 'Remote database table does not contain field(s) "{$a}"';
$string['dbmissingtablex'] = 'Remote database table "{$a}" does not exist';
$string['dbtestconnection'] = 'Test database connection';
$string['dbtestconnecting'] = 'Connecting...';
$string['dbtestconnectsuccess'] = 'Successfully connected to database';
$string['dbtestconnectfail'] = 'Failed to connect to database';

$string['dbconnectiondetails'] = 'Please enter database connection details.';
$string['selectfieldsdb'] = 'Please select some fields to import by checking the boxes below.';
$string['tablemustincludexdb'] = 'The database table must contain the following fields:';

$string['databaseemptynullinfo'] = 'The use of empty strings in your external database will delete the field\'s value in your site. Null values in your external database will leave the field\'s current value in your site.';

///
/// Log messages
///
$string['syncnotconfiguredsummary'] = 'HR Import is not configured properly. Please, fix the issues before running: {$a}';
$string['syncnotconfigured'] = 'HR Import is not configured properly. Please, fix the issues before running.';
$string['temptableprepfail'] = 'temp table preparation failed';
$string['temptablecreatefail'] = 'error creating temp table';
$string['nofilesdir'] = 'No HR Import files directory configured';
$string['nofiletosync'] = 'No file to import (file path: {$a})';
$string['nofileuploaded'] = 'No file was uploaded for {$a} import';
$string['nochangesskippingsync'] = 'no changes, skipping import';
$string['cannotopenx'] = 'cannot open {$a}';
$string['cannotreadx'] = 'cannot read {$a}';
$string['csvnotvalidmissingfieldx'] = 'CSV file not valid, missing field "{$a}"';
$string['csvnotvalidmissingfieldxmappingx'] = 'CSV file not valid, missing field "{$a->mapping}" (mapping for "{$a->field}")';
$string['csvnotvalidinvalidchars'] = 'CSV file not valid. It contains invalid characters ("{$a->invalidchars}"). Fields in a CSV file must be separated by a selected delimiter ("{$a->delimiter}").';
$string['couldnotimportallrecords'] = 'could not import all records. Error details: {$a}';
$string['lengthlimitexceeded'] = 'value "{$a->value}" is too long for "{$a->field}" field. It cannot be longer than {$a->length} characters. Skipped {$a->source} {$a->idnumber}';
$string['syncstarted'] = 'HR Import started';
$string['syncfinished'] = 'HR Import finished';
$string['couldnotgetsourcetable'] = 'could not get source table, aborting...';
$string['couldnotcreateclonetable'] = 'could not create clone table, aborting...';
$string['sanitycheckfailed'] = 'sanity check failed, aborting...';
$string['cannotdeletex'] = 'cannot delete {$a} (might already be deleted)';
$string['deletedx'] = 'deleted {$a}';
$string['deletefieldmissingnotallrecords'] = 'The delete field is missing, this is a required field if the file does not contain all records';
$string['suspendeduserx'] = 'suspended {$a}';
$string['existingitemxframeworkidnotfound'] = 'item {$a} does not have a framework id number, this is required when framework id of the existing item is empty';
$string['frameworkxnotfound'] = 'framework {$a} not found...';
$string['parentxnotfound'] = 'parent {$a} not found...';
$string['cannotsyncitemparent'] = 'cannot import item\'s parent {$a}';
$string['cannotcreatex'] = 'cannot create {$a}';
$string['cannotcreatedirx'] = 'cannot create directory: {$a}';
$string['createdx'] = 'created {$a}';
$string['cannotupdatex'] = 'cannot update {$a}';
$string['updatedx'] = 'updated {$a}';
$string['frameworkxnotexist'] = 'framework {$a} does not exist';
$string['parentxnotexist'] = 'parent {$a} does not exist';
$string['parentxnotexistinfile'] = 'parent {$a} does not exist in HR Import file';
$string['typexnotexist'] = 'type {$a} does not exist';
$string['circularreferror'] = 'circular reference error between items {$a->naughtynodes}';
$string['customfieldsnotype'] = 'custom fields specified, but no type {$a}';
$string['typexnotfound'] = 'type {$a} not found...';
$string['customfieldnotexist'] = 'custom field {$a->shortname} does not exist (type:{$a->typeidnumber})';
$string['customfieldinvalidmaptype'] = 'While processing item {$a->idnumber}: the custom field column, {$a->columnname}, is not valid for type: {$a->typeidnumber}';
$string['cannotdeleteuseradmin'] = 'Local administrator accounts can not be deleted: {$a}';
$string['cannotdeleteuserguest'] = 'Guest user account can not be deleted: {$a}';
$string['cannotdeleteuserx'] = 'cannot delete user {$a}';
$string['deleteduserx'] = 'deleted user {$a}';
$string['syncaborted'] = 'HR Import aborted';
$string['cannotupdatedeleteduserx'] = 'cannot undelete user {$a}';
$string['cannotupdateuserx'] = 'cannot update user {$a}';
$string['cannotsetuserpassword'] = 'cannot set user password (user:{$a})';
$string['cannotsetuserpasswordnoauthsupport'] = 'cannot set user password (user:{$a}), auth plugin does not support password changes';
$string['updateduserx'] = 'updated user {$a}';
$string['reviveduserx'] = 'revived user {$a}';
$string['cannotreviveuserx'] = 'cannot revive user {$a}';
$string['createduserx'] = 'created user {$a}';
$string['cannotcreateuserx'] = 'cannot create user {$a}';
$string['invalidauthforuserx'] = 'invalid authentication plugin {$a}';
$string['invalidauthxforuserx'] = 'invalid authentication plugin {$a->auth} for user {$a->idnumber}';
$string['optionxnotexist'] = 'Option \'{$a->option}\' does not exist for {$a->fieldname} field. Please check user {$a->idnumber}';
$string['fieldrequired'] = '{$a->fieldname} is a required field and must have a value. Please check user {$a->idnumber}';
$string['fieldduplicated'] = 'The value \'{$a->value}\' for {$a->fieldname} is a duplicate of existing data and must be unique. Skipped user {$a->idnumber}';
$string['fieldduplicateddate'] = 'The date of {$a->date} ({$a->timestamp}) for {$a->fieldname} is a duplicate of existing data and must be unique. Skipped user {$a->idnumber}';
$string['fieldmustbeunique'] = 'The value \'{$a->value}\' for {$a->fieldname} is duplicated in the uploaded data and must be unique. Skipped user {$a->idnumber}';
$string['nosourceconfigured'] = 'No source configured, please set configuration <a href=\'{$a}\'>here</a>';
$string['duplicateuserswithidnumberx'] = 'Duplicate users with idnumber {$a->duplicatefield}. Skipped user {$a->idnumber}';
$string['duplicateuserswithusernamex'] = 'Duplicate users with username {$a->duplicatefield}. Skipped user {$a->idnumber}';
$string['duplicateuserswithemailx'] = 'Duplicate users with email {$a->duplicatefield}. Skipped user {$a->idnumber}';
$string['duplicateusernamexdb'] = 'Username {$a->username} is already registered. Skipped user {$a->idnumber}';
$string['duplicateusersemailxdb'] = 'Email {$a->email} is already registered. Skipped user {$a->idnumber}';
$string['duplicateidnumberx'] = 'Duplicate idnumber {$a}';
$string['emptyemailusercreation'] = 'Empty email address. Skipped user {$a->idnumber}';
$string['emptyvalueauthx'] = 'Auth cannot be empty. Skipped user {$a->idnumber}';
$string['emptyvalueemailx'] = 'Email cannot be empty (duplicates not allowed). Skipped user {$a->idnumber}';
$string['emptyvaluefirstnamex'] = 'First name cannot be empty. Skipped user {$a->idnumber}';
$string['emptyvalueidnumberx'] = 'Idnumber cannot be empty. Skipped user {$a->idnumber}';
$string['emptyvaluelastnamex'] = 'Last name cannot be empty. Skipped user {$a->idnumber}';
$string['emptyvaluepasswordx'] = 'Password cannot be empty. Skipped user {$a->idnumber}';
$string['emptyvalueusernamex'] = 'Username cannot be empty. Skipped user {$a->idnumber}';
$string['fieldcountmismatch'] = 'Skipping row {$a->rownum} in CSV file - {$a->fieldcount} fields found but {$a->headercount} fields expected. Please make sure fields are separated by a selected delimiter ("{$a->delimiter}").';
$string['invalidcountrycode'] = 'Invalid country code {$a->country} for user {$a->idnumber}';
$string['invaliddateformatforfield'] = 'Invalid date format for field {$a}';
$string['invaliddateformatforfieldforuser'] = 'Invalid date format for field {$a->field} for user {$a->user}';
$string['invalidemailx'] = 'Invalid email address. Skipped user {$a->idnumber}';
$string['invalidlangx'] = 'Invalid language specified for user {$a->idnumber}';
$string['invalidusernamex'] = 'User {$a->idnumber} has a username, \'{$a->username}\', containing invalid characters. It will not be imported. Please update your source data.';
$string['invalidcaseusernamex'] = 'User {$a->idnumber} has a username, \'{$a->username}\', containing mixed case characters. It will be imported with the username converted to lower case. Please update your source data accordingly.';
$string['nosynctablemethodforsourcex'] = 'Source {$a} has no get_sync_table method. This needs to be fixed by a programmer.';
$string['sourcefilexnotfound'] = 'Source file {$a} not found.';
$string['sourceclassxnotfound'] = 'Source class {$a} not found. This must be fixed by a programmer.';
$string['nosourceenabled'] = 'No source enabled for this element.';
$string['usersyncdisabled'] = 'Skipped user {$a->idnumber} as their HR Import setting is disabled.';

$string['syncexecute'] = 'Run HR Import';
$string['runsynccronstart'] = 'Running HR Import cron...';
$string['runsynccronend'] = 'Done!';
$string['runsynccronendwithproblem'] = 'However, there have been some problems';
$string['deleteallsynclog'] = 'Clear all records';
$string['deletepartialsynclog'] = 'Clear all except latest records';
$string['deleteallsynclogcheck'] = 'Are you absolutely sure you want to delete all the HR Import log records?';
$string['deletepartialsynclogcheck'] = 'Are you absolutely sure you want to delete all the HR Import log records except for the most recent run?';
$string['error:deletesynclogpermission'] = 'You do not have permission to delete HR Import Log records!';

///
/// HR Import log reports
///
$string['synclog'] = 'HR Import Log';
$string['viewsynclog'] = 'View the results in the HR Import Log <a href=\'{$a}\'>here</a>';
$string['sourcetitle'] = 'HR Import Log';
$string['datetime'] = 'Date/Time';
$string['logtype'] = 'Log type';
$string['error'] = 'Error';
$string['info'] = 'Info';
$string['warn'] = 'Warning';
$string['action'] = 'Action';
$string['info'] = 'Info';
$string['id'] = 'Id';
$string['runid'] = 'Run ID';
$string['datetime'] = 'Date/Time';
$string['element'] = 'Element';
$string['action'] = 'Action';
$string['info'] = 'Info';

///
/// HR Import help strings
///
$string['country_help'] = 'This should be formatted within the CSV as the 2 character code of the country. For example \'New Zealand\' should be \'NZ\', see <a href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2">http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2</a> for details';
$string['fileaccess_help'] = 'The options are:

* **Directory**: This option allows you to specify a directory on the server to be checked for HR Import files automatically.
* **Upload**: This option requires you to upload files via the **upload HR Import files** page under sources in site administration.';
//Delimiter strings
$string['delimiter'] = 'Delimiter';
$string['comma'] = 'Comma (,)';
$string['semicolon'] = 'Semi-colon (;)';
$string['colon'] = 'Colon (:)';
$string['tab'] = 'Tab (\t)';
$string['pipe'] = 'Pipe (|)';

$string['errorplural'] = 'Errors';
$string['notifymessage'] = 'Server time: {$a->time}, Element: {$a->element}, Action: {$a->action}, {$a->logtype}: {$a->info}';
$string['notifymessagestartrunid'] = '{$a->count} new HR Import log messages ({$a->logtypes}) For run id {$a->runid}. See below for most recent messages:';
$string['notifysubject'] = '{$a} :: HR Import notification';
$string['syncnotifications'] = 'HR Import notifications';
$string['viewsyncloghere'] = 'For more information, view the HR Import Log at {$a}';
$string['warnplural'] = 'Warnings';
$string['emptyfieldsbehaviourhierarchy'] = 'Empty string behaviour in CSV';
$string['emptyfieldsbehaviouruser'] = 'Empty string behaviour in CSV';
$string['emptyfieldskeepdata'] = 'Empty strings are ignored';
$string['emptyfieldsremovedata'] = 'Empty strings erase existing data';
$string['emptyfieldsbehaviourhierarchy_help'] = 'When set to **Empty strings are ignored** empty strings within your CSV file will result in the current value being left.

When set to **Empty strings erase existing data** empty strings within your CSV file will lead to the current value being deleted.';
$string['emptyfieldsbehaviouruser_help'] = 'When set to **Empty strings are ignored** empty strings within your CSV file will result in the current value being left.

When set to **Empty strings erase existing data** empty strings within your CSV file will lead to the current value being deleted.

Please note that some fields are required, and some fields utilise a default value.

* If **Empty strings erase existing data** is selected and you attempt to delete the current value for a required field, the user in the CSV file will be skipped as a value must be provided.
* If **Empty strings erase existing data** is selected and you delete the current value of a field that utilises a default value, the default value will be used as the current value.

Fields that cannot be empty are:

* idnumber
* username
* firstname
* lastname
* password
* deleted (depending on the source contains all records setting)
* auth
';
$string['eventsynccompleted'] = 'HR Import completed';
$string['files'] = 'Files';
$string['filesdir'] = 'Files directory';
$string['fileaccess'] = 'File access';
$string['fileaccess_default'] = 'Default ({$a})';
$string['fileaccess_directory'] = 'Directory Check';
$string['fileaccess_upload'] = 'Upload Files';
$string['fileaccess_unknowndefault'] = 'Default (Unknown)';
$string['fileaccessnotset'] = 'No valid file access configuration found';
$string['filesdirnotset'] = 'No valid file directory configuration found';
$string['defaultsettings'] = 'Default settings';
$string['invalidemailaddress'] = 'Invalid email address \'{$a}\'';
$string['noneselected'] = 'None selected';
$string['notifications'] = 'Notifications';
$string['notifymailto'] = 'Email notifications to';
$string['notifymailtodefault'] = 'Email notifications to: {$a->recipients}';
$string['notifymailto_help'] = 'A comma-separated list of email addresses so which notifications should be sent.';
$string['notifytypes'] = 'Send notifications for';
$string['notifytypesdefault'] = 'Send notifications for: {$a->logmessagetypes}';
$string['schedule'] = 'Schedule';
$string['scheduledefault_complex'] = 'Complex schedule in use';
$string['scheduledefault_currentsetting'] = 'Schedule (Server time): {$a}';
$string['scheduledhrimporting'] = 'Scheduled HR importing';
$string['scheduledisabled'] = 'Disable';
$string['scheduleenabled'] = 'Enable';
$string['scheduleserver'] = 'Schedule (server time)';
$string['schedulingdisabled'] = 'Scheduling disabled';
$string['usedefaultsettings'] = 'Use default settings';
$string['usedefaultsettings_help'] = 'When selected, settings configured on the \'Default settings\' page will be applied to this element. When deselected, it is possible to override the default with settings specific to this element only.';
$string['csvencoding'] = 'CSV file encoding';
