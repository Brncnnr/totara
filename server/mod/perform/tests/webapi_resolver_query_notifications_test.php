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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\models\activity\notification as notification_model;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\notifications
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_notifications_testcase extends advanced_testcase {

    private const QUERY = 'mod_perform_notifications';

    use webapi_phpunit_helper;

    private function create_test_data(): array {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container();

        $notification = notification_model::load_by_activity_and_class_key($activity, 'instance_created');
        $notification2 = notification_model::load_by_activity_and_class_key($activity, 'instance_created_reminder');

        return [$notification, $notification2];
    }

    private function key_as_class_key(array $array) {
        return array_combine(array_column($array, 'class_key'), $array);
    }

    public function test_get_notifications(): void {
        $notifications = $this->create_test_data();

        $args = ['activity_id' => $notifications[0]->activity->id];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assertDebuggingCalledCount(2);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $result = $this->key_as_class_key($result);
        $this->assertEquals($notifications[0]->id, $result['instance_created']['id']);
        $this->assertSame($notifications[0]->name, $result['instance_created']['name']);
        $this->assertSame($notifications[0]->active, $result['instance_created']['active']);
        $this->assertSame($notifications[0]->trigger_label, $result['instance_created']['trigger_label']);
        $this->assertEquals($notifications[1]->id, $result['instance_created_reminder']['id']);
        $this->assertSame($notifications[1]->name, $result['instance_created_reminder']['name']);
        $this->assertSame($notifications[1]->active, $result['instance_created_reminder']['active']);
        $this->assertSame($notifications[1]->trigger_label, $result['instance_created_reminder']['trigger_label']);
        unset($result['instance_created'], $result['participant_selection'], $result['instance_created_reminder']);
        foreach ($result as $class_key => $one) {
            $this->assertNotNull($one['id']);
            $this->assertEquals($class_key, $one['class_key']);
            $this->assertSame($one['name'], mod_perform\notification\factory::create_loader()->get_name_of($one['class_key']), $class_key);
            $this->assertFalse($one['active'], $class_key);
            $this->assertSame($one['trigger_label'], mod_perform\notification\factory::create_loader()->get_trigger_label_of($one['class_key']), $class_key);
        }
    }

    public function test_failed_ajax_query(): void {
        $notifications = $this->create_test_data();
        $args = ['activity_id' => $notifications[0]->activity->id];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assertDebuggingCalledCount(1);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assertDebuggingCalledCount(0);
        $this->assert_webapi_operation_failed($result, 'activity_id');

        $result = $this->parsed_graphql_operation(self::QUERY, ['activity_id' => 0]);
        $this->assertDebuggingCalledCount(0);
        $this->assert_webapi_operation_failed($result, 'Argument "activity_id" of non-null type "core_id!" must not be null.');

        $result = $this->parsed_graphql_operation(self::QUERY, ['activity_id' => 1293]);
        $this->assertDebuggingCalledCount(1);
        $this->assert_webapi_operation_failed($result, "Invalid activity");

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assertDebuggingCalledCount(1);
        $this->assert_webapi_operation_failed($result, 'not logged in');

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assertDebuggingCalledCount(1);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }
}
