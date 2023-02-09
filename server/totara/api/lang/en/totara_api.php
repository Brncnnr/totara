<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_api
 */

defined('MOODLE_INTERNAL') || die();

$string['actions_for'] = 'Actions for {$a}';
$string['add_client'] = 'Add client';
$string['apisettings'] = 'API settings';
$string['back_to_api'] = 'Back to API';
$string['changes_saved'] = 'Changes saved.';
$string['clients'] = 'API clients';
$string['client_added'] = 'Client added.';
$string['client_details'] = 'Clients';
$string['client_id'] = 'Client ID';
$string['client_name_disabled'] = '{$a} (disabled)';
$string['client_rate_limit'] = 'Client rate limit';
$string['client_rate_limit_at_site_level'] = 'Client rate limit at site level: {$a}';
$string['client_rate_limit_desc'] = 'Maximum query complexity cost allowed per minute for this client. If this limit exceeds the site limit, the site limit will be enforced.';
$string['client_secret'] = 'Client secret';
$string['delete_client_name'] = 'Delete client: {$a}';
$string['delete_modal_title'] = 'Delete client';
$string['delete_confirm_title'] = 'Are you sure you want to delete this client?';
$string['delete_confirm_body'] = '\'{$a}\' will be permanently removed and your site will not receive any further data from this provider. You should also remove it from any other sites where it is used.';
$string['delete_success'] = 'Client deleted.';
$string['description'] = 'Description';
$string['developer'] = 'Developer';
$string['disable_client'] = 'Disable';
$string['disable_client_error'] = 'Client could not be disabled.';
$string['disable_client_name'] = 'Disable client: {$a}';
$string['disable_client_success'] = 'Client disabled.';
$string['documentation'] = 'API documentation';
$string['duration_days'] = 'days';
$string['duration_hours'] = 'hours';
$string['duration_minutes'] = 'minutes';
$string['duration_seconds'] = 'seconds';
$string['duration_units_label'] = '{$a} units';
$string['duration_value_label'] = '{$a} number';
$string['duration_weeks'] = 'weeks';
$string['edit_client_details'] = 'Edit client details';
$string['edit_client_details_name'] = 'Edit client details: {$a}';
$string['edit_client_settings'] = 'Edit client settings';
$string['edit_client_settings_name'] = 'Edit client settings: {$a}';
$string['enable_client'] = 'Enable';
$string['enable_client_error'] = 'Client could not be enabled.';
$string['enable_client_name'] = 'Enable client: {$a}';
$string['enable_client_success'] = 'Client enabled.';
$string['enable_api'] = 'Enable API';
$string['enable_api_description'] = 'Allow external systems to connect to your Totara site, to access data and perform operations. When enabled, you can access settings and configure the API from the <a href="{$a}">development menu</a>.';
$string['error_response'] = 'Error response';
$string['error_response_default'] = 'Default: ';
$string['error_response_desc_1'] = 'Determines the amount of information returned by an API response when an error occurs.';
$string['error_response_desc_2'] = '<ul>
<li><strong>None:</strong> Return generic error with no specific information</li>
<li><strong>Normal:</strong> Return the type of error</li>
<li><strong>Developer:</strong> Return error with stack trace for developers</li>
</ul>';
$string['error_response_level_default'] = 'Site default ({$a})';
$string['error_validate_max_input_number'] = 'Number must be {$a} or less';
$string['error_validate_max_input_duration'] = 'Duration must be {$a} seconds or less';
$string['hide'] = 'Hide';
$string['none'] = 'None';
$string['normal'] = 'Normal';
$string['invalid_user'] = 'Invalid user';
$string['no_record_found'] = 'No clients have been created.';
$string['pluginname'] = 'API';
$string['required_fields'] = 'Required fields';
$string['service_account'] = 'Service account';
$string['service_account_help'] = 'The service account is a user account that represents an external system that can control your Totara site, and should be assigned a role with appropriate capabilities.';
$string['service_account_placeholder'] = 'Start typing a name...';
$string['setting:client_rate_limit'] = 'Client rate limit';
$string['setting:client_rate_limit_desc'] = 'Maximum query complexity cost allowed per minute for an individual client on this site. This limit can be reduced for individual clients within client settings.';
$string['setting:default_token_expiration'] = 'Default token expiration';
$string['setting:default_token_expiration_desc'] = 'Length of time that a token will be valid, before expiration. This default is used when adding new clients.';
$string['setting:default_token_expiration_invalid'] = 'Duration must be 1 second or more';
$string['setting:enable_introspection'] = 'Enable GraphQL introspection';
$string['setting:enable_introspection_desc'] = 'Allow clients to ask for information about the GraphQL schema. This includes data like types, fields, queries and mutations.';
$string['setting:max_query_complexity'] = 'Maximum query complexity';
$string['setting:max_query_complexity_desc'] = 'Maximum complexity allowed for an individual query.';
$string['setting:max_query_depth'] = 'Maximum query depth';
$string['setting:max_query_depth_desc'] = 'Maximum depth allowed for an individual query.';
$string['setting:response_debug'] = 'Default error response';
$string['setting:response_debug_desc'] = 'Determines the amount of information returned by an API response when an error occurs.  
**None:** Return generic error with no specific information  
**Normal:** Return the type of error  
**Developer:** Return error with stack trace for developers';
$string['setting:site_rate_limit'] = 'Site rate limit';
$string['setting:site_rate_limit_desc'] = 'Maximum query complexity cost allowed per minute on this site.';
$string['settings'] = 'API settings';
$string['show'] = 'Show';
$string['status'] = 'Status';
$string['status_disabled'] = 'Disabled';
$string['status_enabled'] = 'Enabled';
$string['service_account'] = 'Service account';
$string['service_account_invalid'] = 'Invalid service account for {$a}';
$string['token_expiration'] = 'Token expiration';
$string['token_expiration_default'] = '{$a->value} {$a->units}';
$string['token_expiration_desc_1'] = 'Length of time that a token will be valid, before expiration.';
$string['token_expiration_desc_2'] = 'Changing this setting only impacts new tokens; any existing tokens will continue to honour the expiry time set at the time of their creation.';
$string['warning_client_rate_limit'] = 'This client\'s rate limit exceeds the site limit, so the site limit is being enforced.';

//Errors
$string['error_documentation_browser_support'] = 'Your browser isn\'t supported on this page. You can view this page in any modern browser.';
$string['error_documentation_not_found'] = 'Documentation not found. You must build documentation to view this page.';
$string['error_documentation_parse_error'] = 'Documentation couldn\'t be parsed. Try clearing the site cache and rebuilding documentation to resolve this issue.';
$string['error_documentation_schema_changed'] = 'Schema has changed since documentation was last built. Try clearing the site cache and rebuilding documentation to show the current schema.';
$string['error_service_account_admin'] = 'The service account can not be a site administrator';
$string['error_service_account_deleted'] = 'This user account has been deleted';
$string['error_service_account_guest'] = 'The service account can not be a guest';
$string['error_service_account_invalid'] = 'This user account is invalid';
$string['error_service_account_no_user'] = 'The user account was not found';
$string['error_service_account_suspended'] = 'This user account has been suspended';
$string['error_service_account_wrong_tenant'] = 'This user account has the wrong tenant membership';

//Capabilities
$string['api:manageclients'] = 'Manage API clients';
$string['api:managesettings'] = 'Manage API client settings';
$string['api:viewdocumentation'] = 'View API documentation';
