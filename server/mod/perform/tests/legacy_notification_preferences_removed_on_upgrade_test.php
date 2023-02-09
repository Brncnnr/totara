<?php
/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Ben Fesili <ben.fesili@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;

/**
 * @group perform
 */
class mod_perform_legacy_notification_preferences_removed_on_upgrade_test extends testcase {
    public function test_upgrade_removes_just_perform_message_providers(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/perform/db/upgradelib.php');

        mod_perform_remove_legacy_message_providers();

        $message_providers = $DB->get_records('message_providers');
        $plugins = $DB->get_records('config_plugins');
        $user_preferences = $DB->get_records('user_preferences');

        // ensure the message providers have been removed
        $components = array_column($message_providers, 'component');
        $components = array_flip($components);
        $this->assertArrayNotHasKey('mod_perform', $components);

        // ensure the plugin configs have been removed
        $plugin_names = array_column($plugins, 'name');
        $plugin_names = array_flip($plugin_names);
        $this->assertArrayNotHasKey('email_provider_mod_perform_activity_notification_permitted', $plugin_names);
        $this->assertArrayNotHasKey('email_provider_mod_perform_activity_reminder_permitted', $plugin_names);
        // ensure other message plugins have not been touched
        $this->assertArrayHasKey('persistent_mod_perform_persistent', $plugin_names);
        $this->assertArrayHasKey('email_provider_mod_persistent_activity_reminder_permitted', $plugin_names);

        // ensure the user preferences have been removed
        $user_preference_names = array_column($user_preferences, 'name');
        $user_preference_names = array_flip($user_preference_names);
        $this->assertArrayNotHasKey('message_provider_mod_perform_activity_notification_loggedin', $user_preference_names);
        $this->assertArrayNotHasKey('message_provider_mod_perform_activity_reminder_loggedoff', $user_preference_names);
        // ensure other user preferences have not been touched
        $this->assertArrayHasKey('message_provider_mod_persistent_activity_notification_loggedin', $user_preference_names);
        $this->assertArrayHasKey('something_completely_different', $user_preference_names);
    }

    protected function setUp(): void {
        parent::setUp();
        global $DB;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // create the message providers
        $providers = [];
        $notification_provider = new stdClass();
        $notification_provider->name = 'activity_notification';
        $notification_provider->component = 'mod_perform';
        $providers[] = $notification_provider;
        $reminder_provider = new stdClass();
        $reminder_provider->name = 'activity_reminder';
        $reminder_provider->component = 'mod_perform';
        $providers[] = $reminder_provider;
        $persistent_provider = new stdClass();
        $persistent_provider->name = 'activity_notification';
        $persistent_provider->component = 'mod_persistent';
        $providers[] = $persistent_provider;

        $DB->insert_records('message_providers', $providers);

        // create the config plugin records
        $configparams = [
            [
                'plugin' => 'message',
                'name' => 'email_provider_mod_perform_activity_notification_permitted',
                'value' => 'permitted',
            ],
            [
                'plugin' => 'message',
                'name' => 'email_provider_mod_perform_activity_reminder_permitted',
                'value' => 'permitted',
            ],
            [
                'plugin' => 'persistent',
                'name' => 'persistent_mod_perform_persistent',
                'value' => 'permitted',
            ],
            [
                'plugin' => 'message',
                'name' => 'email_provider_mod_persistent_activity_reminder_permitted',
                'value' => 'permitted',
            ],
        ];

        $DB->insert_records('config_plugins', $configparams);

        // create the user preferences
        $users = [$user1, $user2];
        $preferences = [];

        $providers = [
            'message_provider_mod_perform_activity_notification_loggedin',
            'message_provider_mod_perform_activity_reminder_loggedoff',
            'message_provider_mod_persistent_activity_notification_loggedin',
            'something_completely_different',
        ];

        foreach ($users as $user) {
            foreach ($providers as $provider) {
                $preference = new stdClass();
                $preference->userid = $user->id;
                $preference->name = $provider;
                $preference->value = 'popup,email';
                $preferences[] = $preference;
            }
        }

        $DB->insert_records('user_preferences', $preferences);
    }
}