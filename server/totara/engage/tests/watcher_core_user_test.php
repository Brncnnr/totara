<?php
/**
 * This file is part of Totara LMS
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
 * @author  Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die;

class totara_engage_watcher_core_user_testcase extends advanced_testcase {

    private const ENGAGE_FEATURES = [
        'engage_resources',
        'container_workspace',
        'ml_recommender',
        'totara_msteams',
    ];

    public function test_allow_view_profile_based_on_features() {
        $hook = new \core_user\hook\allow_view_profile(64, 66);

        foreach (self::ENGAGE_FEATURES as $feature) {
            \totara_core\advanced_feature::disable($feature);
        }

        \totara_engage\watcher\core_user::handle_allow_view_profile($hook);
        self::assertFalse($hook->has_permission());

        foreach (self::ENGAGE_FEATURES as $feature) {
            \totara_core\advanced_feature::enable($feature);
        }

        \totara_engage\watcher\core_user::handle_allow_view_profile($hook);
        self::assertTrue($hook->has_permission());
    }

    public function test_allow_view_profile_permission_already_granted() {
        $hook = new \core_user\hook\allow_view_profile(64, 66);
        $hook->give_permission();

        // It does not matter that the users are invalid, as permission has been granted already.
        \totara_engage\watcher\core_user::handle_allow_view_profile($hook);
        self::assertTrue($hook->has_permission());
    }

    public function test_allow_view_profile_permission_forced_on() {
        global $CFG;

        $CFG->totara_engage_allow_view_profiles = true;
        $hook = new \core_user\hook\allow_view_profile(64, 66);

        // It does not matter that the users are invalid, as it is forced on.
        \totara_engage\watcher\core_user::handle_allow_view_profile($hook);
        self::assertTrue($hook->has_permission());
    }

    public function test_allow_view_profile_permission_forced_off() {
        global $CFG;

        $CFG->totara_engage_allow_view_profiles = false;
        $hook = new \core_user\hook\allow_view_profile(64, 66);

        // It does not matter that the users are invalid, as it is forced on.
        \totara_engage\watcher\core_user::handle_allow_view_profile($hook);
        self::assertFalse($hook->has_permission());
    }

    public function test_allow_view_profile_permission_forced_incorrectly() {
        global $CFG;

        $CFG->totara_engage_allow_view_profiles = '1';
        $hook = new \core_user\hook\allow_view_profile(64, 66);

        foreach (self::ENGAGE_FEATURES as $feature) {
            \totara_core\advanced_feature::disable($feature);
        }

        \totara_engage\watcher\core_user::handle_allow_view_profile($hook);
        self::assertFalse($hook->has_permission());

        foreach (self::ENGAGE_FEATURES as $feature) {
            \totara_core\advanced_feature::enable($feature);
        }

        \totara_engage\watcher\core_user::handle_allow_view_profile($hook);
        self::assertTrue($hook->has_permission());
    }

}