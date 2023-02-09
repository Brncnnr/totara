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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform;

use admin_externalpage;
use context_coursecat;
use context_user;
use core\entity\user;
use mod_perform\controllers\activity\manage_activities;
use mod_perform\controllers\reporting\performance\activity_response_data;
use moodle_url;

/**
 * The settings in this class are deliberately not part of the usual settings.php.
 *
 * Currently the settings.php file in the plugin is not parsed unless the user
 * has the moodle/site:config or totara/core:modconfig capability.
 *
 * This class is currently read by the admin/settings/perform.php which does not require
 * one of those two capabilities
 *
 * @see TL-24292 for a possible generic way to implement this in the future
 *
 * @package mod_perform
 */
class settings {

    public static function init_public_settings(\admin_root $admin_root) {
        static::add_manage_activities_link($admin_root);
        static::add_response_reporting_link($admin_root);
    }

    /**
     * Users who have the 'global' response reporting capability should see the admin link
     *
     * Those who only have the 'individual' capability (in the context of a specific user) need to access
     * the page either through the Team page (if they are a manager) or from their own user profile.
     *
     * @param \admin_root $admin_root
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private static function add_response_reporting_link(\admin_root $admin_root) {
        $admin_root->add(
            'performactivities',
            new admin_externalpage(
                'mod_perform_activity_response_data',
                get_string('menu_title_activity_response_data', 'mod_perform'),
                new moodle_url(activity_response_data::URL),
                'mod/perform:report_on_all_subjects_responses',
                false,
                isloggedin() ? context_user::instance(user::logged_in()->id) : null
            )
        );
    }

    private static function add_manage_activities_link(\admin_root $admin_root) {
        global $USER;
        // Save $USER->access as the subsequent has_capability() trashes it.
        // This is necessary for the guest enrolment plugin that loads a temp role to $USER->access.
        $user_access = $USER->access ?? null;

        $category_id = util::get_default_category_id();
        $category_context = context_coursecat::instance($category_id);
        if (has_capability('mod/perform:view_manage_activities', $category_context)) {
            $admin_root->add(
                'performactivities',
                new admin_externalpage(
                    'mod_perform_manage_activities',
                    get_string('menu_title_activity_management', 'mod_perform'),
                    new moodle_url(manage_activities::URL),
                    'mod/perform:view_manage_activities',
                    false,
                    $category_context
                )
            );
        }

        // Restore $USER->access.
        if ($user_access) {
            $USER->access = $user_access;
        }
    }
}
