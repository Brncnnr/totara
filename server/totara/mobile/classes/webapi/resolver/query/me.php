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
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\query;

use core\webapi\execution_context;
use core_user\access_controller;

class me extends \core\webapi\query_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $DB, $USER, $CFG;

        $requirepasswordchange = false;
        $requirepolicyagree = false;
        $requireuserconsent = false;
        try {
            // Note: This isn't using middleware since we couldn't figure out how to replicate this try catch.
            require_login(null, false, null, false, true);
        } catch (\moodle_exception $e) {
            // Unique handling required for sitepolicy, user consent, and forcepwchange errors.
            // Any other errors should be re-thrown.
            switch ($e->errorcode) {
                case 'forcepasswordchangenotice':
                    $requirepasswordchange = true;
                    break;
                case 'sitepolicynotagreed':
                    $requirepolicyagree = true;
                    break;
                case 'sitepolicyconsentpending':
                    $requireuserconsent = true;
                    break;
                default:
                    throw $e;
            }
        }

        require_capability('totara/mobile:use', \context_user::instance($USER->id));

        $user = $DB->get_record('user', ['id' => $USER->id, 'deleted' => 0], '*', MUST_EXIST);

        $userfieldsmissing = false;
        if (!profile_has_required_custom_fields_set($USER->id)) {
            if (exists_auth_plugin($USER->auth)) {
                $auth = get_auth_plugin($USER->auth);
                if ($auth->can_edit_profile() and has_capability('moodle/user:editownprofile', \context_user::instance($USER->id))) {
                    $userfieldsmissing = true;
                }
            }
        }

        $plugininfo = [];
        $manager = \core_plugin_manager::instance();
        foreach ($manager->get_installed_plugins('mobile') as $plugin => $version) {
            // Eventually we should check if these are enabled and either return that info or remove the item.
            // but for now we just need a list of plugins and their version.
            $plugininfo[] = ['name' => $plugin, 'version' => $version];
        }

        $controller = access_controller::for($USER, null);
        $system = [
            'wwwroot' => $CFG->wwwroot . '/',
            'apiurl' => $CFG->wwwroot . '/totara/mobile/api.php',
            'release' => $CFG->totara_release, // This is not a security problem, clients do need to know the exact version.
            'request_policy_agreement' => $requirepolicyagree,
            'request_user_consent' => $requireuserconsent,
            'request_user_fields' => $userfieldsmissing,
            'password_change_required' => $requirepasswordchange,
            'view_own_profile' => $controller->can_view_profile(),
            'mobile_subplugins' => $plugininfo,
        ];

        return ['user' => $user, 'system' => $system];
    }
}
