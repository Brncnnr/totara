<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_oauth2
 */
defined('MOODLE_INTERNAL') || die();

$string['actions_for'] = 'Actions for {$a}';
$string['add_provider'] = 'Add provider';
$string['add_oauth2_provider'] = "Add OAuth 2 provider";
$string['client_id'] = 'Client ID';
$string['client_provider_description'] = 'Description';
$string['client_provider_name'] = 'Name';
$string['client_secret'] = 'Client secret';
$string['continue'] = 'Continue';
$string['delete_confirm_body'] = '<strong>{$a}</strong> will be permanently removed and your site will not 
receive any further data from this provider. You should also remove it from any other sites where it’s used.';
$string['delete_confirm_title'] = 'Are you sure you want to delete this OAuth 2 provider?';
$string['delete_modal_title'] = 'Confirm';
$string['delete_provider_name'] = 'Delete provider: {$a}';
$string['delete_success'] = 'Provider deleted.';
$string['description'] = 'Description';
$string['error_delete_internal_provider'] = 'Can\'t delete an internal provider.';
$string['error_invalid_format'] = 'Description format is invalid';
$string['error_provider_description_length'] = 'Description must not exceed 1024 characters.';
$string['error_provider_name_length'] = 'Name must not exceed 75 characters.';
$string['error_provider_name_missing'] = 'Name must not be blank.';
$string['error_scope_type_missing'] = 'Scope must not be blank.';
$string['error_scope_type'] = 'Invalid scope.';
$string['hide'] = 'Hide';
$string['hide_client_secret_aria'] = 'Hide client secret for {$a}';
$string['no_record_found'] = 'No OAuth 2 providers have been created.';
$string['oauth_url'] = 'OAuth server URL';
$string['oauth_url_desc'] = 'The <strong>OAuth server URL</strong> should be set to:';
$string['oauth_url_title'] = 'Totara supports Experience API (xAPI) and uses OAuth 2.0 to authenticate.';
$string['oauth2providerdetails'] = 'OAuth 2 provider details';
$string["pluginname"] = "OAuth 2 providers";
$string['provider_added'] = 'Provider added.';
$string['required_fields'] = 'Required fields';
$string['scopes'] = 'Scopes';
$string['task_expired_oauth2_tokens'] = 'Expired OAuth2 tokens task';
$string['show'] = 'Show';
$string['show_client_secret_aria'] = 'Show client secret for {$a}';
$string['xapi_url'] = 'xAPI server URL';
$string['xapi_url_desc'] = 'The <strong>xAPI server URL</strong> should be set to:';
$string['xapi_write'] = 'Write access to Experience API (xAPI)';

//Capabilities
$string['oauth2:manageproviders'] = 'Manage OAuth 2 providers';
