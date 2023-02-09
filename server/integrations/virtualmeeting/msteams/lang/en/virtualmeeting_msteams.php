<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package virtualmeeting_msteams
 */

$string['pluginname'] = 'Microsoft Teams';
$string['plugindesc'] = 'Provide Microsoft Teams integration.';
$string['setting_header_app'] = 'App credentials';
$string['setting_header_app_desc'] = 'Please <a href="https://help.totaralearning.com/display/DEV/Setting+up+Microsoft+Teams+Virtualmeeting+plugin">follow our documentation</a> to set up your app on Microsoft Azure.

The <strong>Redirect URI</strong> should be set to: <pre><code>{$a->redirect_url}</code></pre>';
$string['setting_client_id'] = 'Application (client) ID';
$string['setting_client_id_help'] = 'Enter the Application (client) ID provided by Microsoft Azure';
$string['setting_client_secret'] = 'Client secret';
$string['setting_client_secret_help'] = 'Enter the Client secret string (application password) provided by Microsoft Azure';
