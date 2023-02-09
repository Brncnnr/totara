<?php
/*
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\activities
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_activities_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_activities';

    use webapi_phpunit_helper;

    public function test_get_activities() {
        $names = ['Mid year performance', 'End year performance'];
        $this->create_test_data($names);

        /** @var collection|mod_perform\models\activity\activity[] $activities */
        $result = $this->resolve_graphql_query(self::QUERY);
        $activities = $result->pluck('name');

        $this->assertCount(count($names), $activities);
        $this->assertEqualsCanonicalizing($names, $activities);
    }

    public function test_get_activities_sorted_by_name() {
        $names = ['Mid year performance', 'End year performance', 'The Final Lap'];
        $this->create_test_data($names);

        /** @var collection|mod_perform\models\activity\activity[] $activities */
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'query_options' => [
                    'sort_by' => 'name'
                ]
            ]
        );
        $activities = $result->pluck('name');

        $this->assertCount(count($names), $activities);
        $this->assertEquals($names[1], $activities[0]);
        $this->assertEquals($names[0], $activities[1]);
        $this->assertEquals($names[2], $activities[2]);
    }

    public function test_user_needs_view_manage_activities_capability() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $user_role = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('mod/perform:view_manage_activities', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        /** @var mod_perform\models\activity\activity[] $activities */
        $activities = $this->resolve_graphql_query(self::QUERY);

        // There are not activities but also no exceptions
        $this->assertEmpty($activities);

        unassign_capability('mod/perform:view_manage_activities', $user_role->id, SYSCONTEXTID);
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, '(Access the performance activities management interface)');
    }

    public function test_users_can_view_reports_or_manage_other_users_activities() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $names = ['Mid year performance', 'End year performance'];
        $this->create_test_data($names);

        $user_role = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('mod/perform:view_manage_activities', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        /** @var mod_perform\models\activity\activity[] $activities */
        $activities = $this->resolve_graphql_query(self::QUERY);

        $this->assertCount(count($names), $activities);
        // Users who created activities can always manage and view reporting
        foreach ($activities as $activity) {
            $this->assertTrue($activity->can_manage());
            $this->assertTrue($activity->can_view_participation_reporting());
        }

        $this->setUser($user2);

        $activities = $this->resolve_graphql_query(self::QUERY);
        $this->assertEmpty($activities);

        // Let the other user manage only
        assign_capability('mod/perform:manage_activity', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        $activities = $this->resolve_graphql_query(self::QUERY);

        foreach ($activities as $activity) {
            $this->assertTrue($activity->can_manage());
            $this->assertFalse($activity->can_view_participation_reporting());
        }

        // Let the user also see view the reports
        assign_capability('mod/perform:view_participation_reporting', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        $activities = $this->resolve_graphql_query(self::QUERY);

        foreach ($activities as $activity) {
            $this->assertTrue($activity->can_manage());
            $this->assertTrue($activity->can_view_participation_reporting());
        }

        // Let the user just view reports
        unassign_capability('mod/perform:manage_activity', $user_role->id, SYSCONTEXTID);

        foreach ($activities as $activity) {
            $this->assertFalse($activity->can_manage());
            $this->assertTrue($activity->can_view_participation_reporting());
        }
    }

    public function test_get_empty_activities() {
        $this->setAdminUser();

        $activities = $this->resolve_graphql_query(self::QUERY);

        $this->assertCount(0, $activities);
    }

    public function test_get_activities_non_admin() {
        $this->setGuestUser();

        $this->expectException(required_capability_exception::class);

        $this->resolve_graphql_query(self::QUERY);
    }

    public function test_ajax_query() {
        $names = ['Mid year performance', 'End year performance'];
        $this->create_test_data($names);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $activities = $this->get_webapi_operation_data($result);
        $this->assertCount(count($names), $activities, 'wrong count');
    }

    public function test_failed_ajax_query(): void {
        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);
    }

    private function create_test_data(array $activity_names) {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        foreach ($activity_names as $name) {
            $perform_generator->create_activity_in_container(['activity_name' => $name]);
        }
    }
}
