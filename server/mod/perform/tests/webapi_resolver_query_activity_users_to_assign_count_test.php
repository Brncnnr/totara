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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\state\activity\draft;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @covers \mod_perform\webapi\resolver\query\activity_users_to_assign_count
 */
class mod_perform_webapi_resolver_query_activity_users_to_assign_count_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_activity_users_to_assign_count';

    use webapi_phpunit_helper;

    /**
     * We don't need to thoroughly test permissions as the query simply extends the query activity.
     */
    public function test_query_permissions(): void {
        [$args] = $this->create_test_data();

        self::setGuestUser();
        $this->expectException(moodle_exception::class);

        $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_query_successful() {
        [$args] = $this->create_test_data();

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(1, $result);
    }

    public function test_successful_ajax_call(): void {
        [$args] = $this->create_test_data();

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals(1, $result);
    }

    public function test_failed_ajax_query(): void {
        [$args] = $this->create_test_data();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$activity_id" of required type "core_id!" was not provided.');

        $result = $this->parsed_graphql_operation(self::QUERY, ['activity_id' => 0]);
        // activity_id is type of core_id which is converted to NULL resulting in a GraphQL type error
        $this->assert_webapi_operation_failed($result, 'Argument "activity_id" of non-null type "core_id!" must not be null.');

        $id = 1293;
        $result = $this->parsed_graphql_operation(self::QUERY, ['activity_id' => $id]);
        $this->assert_webapi_operation_failed($result, "Invalid activity");

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }

    private function create_test_data(): array {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container([
            'activity_status' => draft::get_code(),
            'create_section' => false,
            'create_track' => false
        ]);

        // Must create a section with an element and a relationship in order to allow an activity to be activated
        $section = $generator->create_section($activity);
        $generator->create_section_element(
            $section,
            $generator->create_element()
        );
        $generator->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_SUBJECT]);

        $track = $generator->create_activity_tracks($activity)->first();
        $user = self::getDataGenerator()->create_user();
        $generator->create_track_assignments_with_existing_groups($track, [], [], [], [$user->id]);

        $args = ['activity_id' => $activity->id];

        return [$args];
    }
}
