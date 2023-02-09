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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\models\activity\section;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__.'/relationship_testcase.php');

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\update_section_settings
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_section_settings_testcase extends mod_perform_relationship_testcase {
    private const MUTATION = 'mod_perform_update_section_settings';
    private const TYPE = 'mod_perform_section';

    use webapi_phpunit_helper;

    public function test_update_section_title(): void {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $activity = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity 1',
            'activity_status' => draft::get_code()
        ]);
        $context = $activity->get_context();

        /** @var section $section1 */
        $section = $perform_generator->create_section($activity, ['title' => 'One']);
        $this->assertEquals('One', $section->title);

        // Entering null means no change.
        $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'section_id' => $section->id,
                'relationships' => [],
                'title' => null,
            ]
        ]);
        $section = section::load_by_id($section->id);
        $this->assertEquals(
            'One',
            $this->resolve_graphql_type(self::TYPE, 'title', $section, [], $context)
        );

        // Empty title
        $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'section_id' => $section->id,
                'relationships' => [],
                'title' => '',
            ]
        ]);
        $section = section::load_by_id($section->id);
        $this->assertEquals(
            '',
            $this->resolve_graphql_type(self::TYPE, 'title', $section, [], $context)
        );
        $this->assertEquals(
            get_string('untitled_section', 'mod_perform'),
            $this->resolve_graphql_type(self::TYPE, 'display_title', $section, [], $context)
        );

        $xss_title = 'Hello<script></script>';
        $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'section_id' => $section->id,
                'relationships' => [],
                'title' => $xss_title,
            ]
        ]);
        $section = section::load_by_id($section->id);
        $this->assertEquals(
            $xss_title,
            $this->resolve_graphql_type(self::TYPE, 'title', $section, [], $context)
        );
        $this->assertEquals(
            'Hello',
            $this->resolve_graphql_type(self::TYPE, 'display_title', $section, [], $context)
        );
    }

    public function test_update_active_activity_not_possible(): void {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $activity = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity 1',
            'activity_status' => active::get_code()
        ]);

        /** @var section $section1 */
        $section = $perform_generator->create_section($activity, ['title' => 'One']);
        $this->assertEquals('One', $section->title);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can\'t update section settings on an active activity.');

        // Entering null means no change.
        $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'section_id' => $section->id,
                'relationships' => [],
                'title' => null,
            ]
        ]);
    }

    public function test_update_invalid_section_id(): void {
        $this->setAdminUser();
        $relationship_id = $this->perform_generator()->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $non_existent_section_id = 1234;
        while (section_entity::repository()->where('id', $non_existent_section_id)->exists()) {
            $non_existent_section_id ++;
        }
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $args = $this->create_args(
            $non_existent_section_id,
            [
                [
                    'id' => $relationship_id,
                    'can_view' => true,
                ]
            ]
        );
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_update_missing_capability(): void {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $relationship_id = $this->perform_generator()->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $activity1 = $perform_generator->create_activity_in_container();
        /** @var section $section1 */
        $section1 = $perform_generator->create_section($activity1);

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $args = $this->create_args(
            $section1->id,
            [
                [
                    'id' => $relationship_id,
                    'can_view' => true,
                ],
            ]
        );
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_update_successful(): void {
        self::setAdminUser();
        $perform_generator = $this->perform_generator();
        $activity1 = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity 1',
            'activity_status' => draft::get_code()
        ]);
        $activity2 = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity 2',
            'activity_status' => draft::get_code()
        ]);

        $appraiser_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $manager_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $subject_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        /** @var section $section1 */
        $section1 = $perform_generator->create_section($activity1);
        $section2 = $perform_generator->create_section($activity1);
        $this->assert_section_relationships($section1, []);
        $this->assert_section_relationships($section2, []);

        // Add three relationships to section1.
        $args = $this->create_args(
            $section1->id,
            [
                [
                    'core_relationship_id' => $subject_relationship->id,
                    'can_view' => true,
                    'can_answer' => false,
                ],
                [
                    'core_relationship_id' => $manager_relationship->id,
                    'can_view' => true,
                    'can_answer' => true,
                ],
                [
                    'core_relationship_id' => $appraiser_relationship->id,
                    'can_view' => false,
                    'can_answer' => true,
                ],
            ]
        );
        $result = $this->resolve_graphql_mutation('mod_perform_update_section_settings', $args);

        /** @var section $returned_section */
        $returned_section = $result['section'];
        $this->assertEquals($section1->id, $returned_section->id);
        $this->assert_section_relationships(
            $section1,
            [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER]
        );
        $this->assert_can_view_and_answer_status($section1, $args['input']['relationships']);
        $this->assert_section_relationships($section2, []);

        // Remove all relationships.
        $args = $this->create_args($section1->id, []);
        $this->resolve_graphql_mutation('mod_perform_update_section_settings', $args);
        $this->assert_section_relationships($section1, []);
        $this->assert_section_relationships($section2, []);
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_ajax_query_successful(): void {
        $data = $this->create_test_data(null, draft::get_code());
        // Section without relationships.
        $section_id = $data->activity2_section2->id;
        $appraiser_relationship = $this->perform_generator()->get_core_relationship(constants::RELATIONSHIP_APPRAISER);

        $args = $this->create_args(
            $section_id,
            [
                [
                    'core_relationship_id' => $appraiser_relationship->id,
                    'can_view' => false,
                ],
            ]
        );

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, "no result");
        $this->assertEquals($section_id, $result['section']['id']);
    }

    public function test_failed_ajax_query(): void {
        $data = $this->create_test_data();
        $section_id = $data->activity2_section2->id;
        $appraiser_relationship = $this->perform_generator()->get_core_relationship(constants::RELATIONSHIP_APPRAISER);

        $args = $this->create_args(
            $section_id,
            [
                [
                    'core_relationship_id' => $appraiser_relationship->id,
                    'can_view' => true,
                ]
            ]
        );

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

    private function create_args(int $section_id, array $relationships): array {
        return [
            'input' => [
                'section_id' => $section_id,
                'relationships' => $relationships
            ]
        ];
    }
}
