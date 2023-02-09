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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\models\activity\activity as activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\section;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\activate_activity
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_add_section_testcase extends advanced_testcase {

    private const MUTATION = 'mod_perform_add_section';

    use webapi_phpunit_helper;

    public function test_add_section(): void {
        [$activity, $args] = $this->create_activity();

        $this->assertCount(1, $activity->get_sections());

        $initial_section = $activity->get_sections()->first();

        $result = $this->resolve_graphql_mutation('mod_perform_add_section', $args);

        /** @var section $section */
        $this->assertArrayHasKey('section', $result);
        $section = $result['section'];
        $this->assertInstanceOf(section::class, $section);

        $activity_reloaded = activity::load_by_id($activity->id);
        $this->assertCount(2, $activity_reloaded->get_sections());

        $this->assertNotEquals($initial_section->id, $section->id);
        $this->assertEmpty($section->title);
    }

    public function test_add_section_on_active_activity_is_not_possible(): void {
        [$activity, $args] = $this->create_activity(active::get_code());

        $this->assertCount(1, $activity->get_sections());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can\'t add a new section on an active activity.');

        $this->resolve_graphql_mutation('mod_perform_add_section', $args);
    }

    public function test_activity_is_not_in_multi_section_mode() {
        /** @var activity $activity */
        [$activity, $args] = $this->create_activity();

        $activity->get_settings()->update([activity_setting::MULTISECTION => false]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Activity is not in multi-section mode');

        $this->resolve_graphql_mutation('mod_perform_add_section', $args);
    }

    public function test_add_section_without_capability(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        [, $args] = $this->create_activity(null, $user1);

        $this->setUser($user2);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        // Calling the resolver directly here as otherwise the middleware would already intervene
        $this->resolve_graphql_mutation('mod_perform_add_section', $args);
    }

    public function test_activate_non_existing_activity(): void {
        $args = [
            'input' => [
                'activity_id' => 999
            ]
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        // Calling the resolver directly here as otherwise the middleware would already intervene
        $this->resolve_graphql_mutation('mod_perform_add_section', $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        [$activity, $args, ] = $this->create_activity();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, $feature);
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$input" of required type "mod_perform_add_section_input!" was not provided.');

        $args['input']['activity_id'] = 0;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid parameter value detected (invalid activity id)');

        $activity_id = 1293;
        $args['input']['activity_id'] = $activity_id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, "Invalid activity");

        self::setGuestUser();
        $args['input']['activity_id'] = $activity->id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }

    /**
     * Creates an activity with one section, one question and one relationship
     *
     * @param int|null $status defaults to draft
     * @param stdClass|null $as_user user that creates the activity.
     * @return array [activity, graphql args] tuple.
     */
    protected function create_activity(?int $status = null, ?stdClass $as_user = null): array {
        if ($as_user) {
            self::setUser($as_user);
        } else {
            self::setAdminUser();
        }

        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container([
            'activity_name' => 'User1 One',
            'activity_status' => $status ?? draft::get_code()
        ]);

        $activity->get_settings()->update([activity_setting::MULTISECTION => true]);

        $args = [
            'input' => [
                'activity_id' => $activity->id
            ]
        ];

        $section = $activity->get_sections()->first();

        $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        return [$activity, $args];
    }
}
