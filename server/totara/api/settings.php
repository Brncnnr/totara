<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package totara_api
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

use totara_api\response_debug;
use totara_api\settings\admin_setting_apiconfigtext;

defined('MOODLE_INTERNAL') || die;

/**
 * When modifying this file, you may also want to update the tenant-level setting navigation defined in
 * {@link totara_api_extend_navigation_category_settings()} in server/totara/api/lib.php.
 */

// Add category for API settings.
/* @var admin_root $ADMIN */
$ADMIN->add(
    'development',
    new admin_category(
        'api',
        new lang_string('pluginname','totara_api')
    ),
    'experimental'
);

// Add manage clients page.
$hidden = totara_core\advanced_feature::is_disabled('api');
$clients_url = new \moodle_url('/totara/api/client/');
$docs_url = new \moodle_url('/totara/api/documentation/');
$context = $PAGE->context;

if ($context instanceof context_coursecat && isset($context->tenantid)) {
    $tenant_id = $context->tenantid;
} else {
    $tenant_id = optional_param('tenant_id', null, PARAM_INT);
    if (!empty($tenant_id)) {
        $tenant = core\record\tenant::fetch($tenant_id, MUST_EXIST);
        $context = context_coursecat::instance($tenant->categoryid);
    }
}

// Check logged-in user is the tenant member or not, if so, set context for capability
// check since quickaccessmenu need to load each nodes when users first logged-in the system.
$current_user = \core\entity\user::logged_in();
if (!empty($current_user->tenantid) && empty($tenant_id) && !$hassiteconfig) {
    $tenant = core\record\tenant::fetch($current_user->tenantid, MUST_EXIST);
    $context = context_coursecat::instance($tenant->categoryid);
}

if (!empty($tenant_id)) {
    $clients_url->param('tenant_id', $tenant_id);
    $docs_url->param('tenant_id', $tenant_id);
}

$ADMIN->add(
    'api',
    new admin_externalpage(
        'totara_api_manage_clients',
        new lang_string('clients','totara_api'),
        $clients_url,
        'totara/api:manageclients',
        $hidden,
        $context
    )
);

$ADMIN->add(
    'api',
    new admin_externalpage(
        'totara_api_documentation',
        new lang_string('documentation','totara_api'),
        $docs_url,
        'totara/api:viewdocumentation',
        $hidden,
        $context
    )
);

// API setting using legacy code.
$settings = new admin_settingpage('api_settings', new lang_string('settings', 'totara_api'), 'moodle/site:config', $hidden);

$ADMIN->add('api', $settings);
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_apiconfigtext('totara_api/site_rate_limit', new lang_string('setting:site_rate_limit', 'totara_api'),
        new lang_string('setting:site_rate_limit_desc', 'totara_api'), '500000', '/^[1-9]\d*$/'));
    $settings->add(new admin_setting_apiconfigtext('totara_api/client_rate_limit', new lang_string('setting:client_rate_limit', 'totara_api'),
        new lang_string('setting:client_rate_limit_desc', 'totara_api'), '250000', '/^[1-9]\d*$/'));
    $settings->add(new admin_setting_apiconfigtext('totara_api/max_query_complexity', new lang_string('setting:max_query_complexity', 'totara_api'),
        new lang_string('setting:max_query_complexity_desc', 'totara_api'), '6000', '/^[1-9]\d*$/'));
    $settings->add(new admin_setting_apiconfigtext('totara_api/max_query_depth', new lang_string('setting:max_query_depth', 'totara_api'),
        new lang_string('setting:max_query_depth_desc', 'totara_api'), '15', '/^[1-9]\d*$/'));
    $token_expiry = new admin_setting_configduration('totara_api/default_token_expiration', new lang_string('setting:default_token_expiration', 'totara_api'),
        new lang_string('setting:default_token_expiration_desc', 'totara_api'), ['v' => 24, 'u' => 3600], 3600); // 24 hours
    $settings->add($token_expiry->set_validator(
        function ($seconds) {
            if ($seconds < 1) {
                return get_string('setting:default_token_expiration_invalid', 'totara_api');
            }
            if ($seconds > admin_setting_apiconfigtext::MAX_INT) {
                return get_string('error_validate_max_input_duration', 'totara_api', admin_setting_apiconfigtext::MAX_INT);
            }
            return null;
        }
    ));
    $settings->add(
        new admin_setting_configcheckbox(
            'totara_api/enable_introspection',
            new lang_string('setting:enable_introspection', 'totara_api'),
            new lang_string('setting:enable_introspection_desc', 'totara_api'),
            0
        )
    );

    $options = [
        response_debug::ERROR_RESPONSE_LEVEL_NONE => get_string('none', 'totara_api'),
        response_debug::ERROR_RESPONSE_LEVEL_NORMAL => get_string('normal', 'totara_api'),
        response_debug::ERROR_RESPONSE_LEVEL_DEVELOPER => get_string('developer', 'totara_api')
    ];
    $settings->add(new admin_setting_configselect('totara_api/response_debug',
        new lang_string('setting:response_debug', 'totara_api'),
        new lang_string('setting:response_debug_desc', 'totara_api'),
        1,
        $options));
    $settings = null;
}