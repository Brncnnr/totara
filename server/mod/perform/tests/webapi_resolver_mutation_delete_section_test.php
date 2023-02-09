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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\delete_section
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_delete_section_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_delete_section';

    use webapi_phpunit_helper;

    public function test_delete_successful() {
        global $DB;

        $data = $this->create_test_data();
        $activity = $data->activity1;
        $section = $data->activity1_section1;

        $args = [
            'input' => [
                'section_id' => $section->id,
            ],
        ];

        $this->assertCount(2, $activity->sections);
        $this->assertCount(2, $section->section_relationships);
        $this->assertCount(1, $section->participant_sections);

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertTrue($result);
        $activity->refresh(true);
        $this->assertCount(1, $activity->sections);
        $section_relationships = $DB->get_records('perform_section_relationship', ['section_id' => $section->id]);
        $this->assertCount(0, $section_relationships);
        $participant_sections = $DB->get_records('perform_participant_section', ['section_id' => $section->id]);
        $this->assertCount(0, $participant_sections);
    }

    public function test_delete_missing_capability() {
        $user = $this->getDataGenerator()->create_user();
        $data = $this->create_test_data();
        $this->setUser($user);

        $activity = $data->activity1;
        $section = $data->activity1_section1;

        $args = [
            'input' => [
                'section_id' => $section->id,
            ],
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_delete_with_invalid_section_id() {
        $this->create_test_data();

        $args = [
            'input' => [
                'section_id' => 12345,
            ],
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_delete_section_fail_on_active_activity() {
        $data = $this->create_test_data();
        $section = $data->activity2_section1;

        $args = [
            'input' => [
                'section_id' => $section->id,
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('section can not be deleted for active performance activity');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_delete_section_fail_when_activity_has_one_section() {
        $data = $this->create_test_data();
        $activity = $data->activity1;
        $section1 = $data->activity1_section1;
        $section2 = $data->activity1_section2;

        $args1 = [
            'input' => [
                'section_id' => $section1->id,
            ],
        ];

        $this->assertCount(2, $activity->sections);

        $this->resolve_graphql_mutation(self::MUTATION, $args1);

        $activity->refresh(true);
        $this->assertCount(1, $activity->sections);

        $args2 = [
            'input' => [
                'section_id' => $section2->id,
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('activity does not have enough sections, section can not be deleted');

        $this->resolve_graphql_mutation(self::MUTATION, $args2);
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_execute_query_successful(): void {
        $data = $this->create_test_data();
        $activity = $data->activity1;
        $section = $data->activity1_section1;

        $args = [
            'input' => [
                'section_id' => $section->id,
            ],
        ];
        $this->assertCount(2, $activity->sections);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $activity->refresh(true);
        $this->assertCount(1, $activity->sections);
    }

    /**
     * @param stdClass|null $as_user
     * @return stdClass
     * @throws coding_exception
     */
    private function create_test_data(?stdClass $as_user = null) {
        if ($as_user) {
            self::setUser($as_user);
        } else {
            self::setAdminUser();
        }
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();

        $user1 = self::getDataGenerator()->create_user();

        /*
         * Set up:
         *
         * activity1 (draft)
         *   - section1_1
         *       - relationship1_1_1: appraiser
         *       - relationship1_1_2: manager
         *       - participant1_1_1
         *   - section1_2
         *       - relationship1_2_1: subject
         *
         * activity2 (active)
         *   - section2_1
         *       - relationship2_1_1: subject
         *   - section2_2
         *
         */
        $data->activity1 = $perform_generator->create_activity_in_container(
            ['activity_name' => 'Activity 1', 'activity_status' => draft::get_code(), 'create_section' => false]
        );
        $data->activity2 = $perform_generator->create_activity_in_container(
            ['activity_name' => 'Activity 2', 'activity_status' => active::get_code(), 'create_section' => false]
        );

        $data->activity1_section1 = $perform_generator->create_section($data->activity1, ['title' => 'Activity 1 section 1']);
        $data->activity1_section2 = $perform_generator->create_section($data->activity1, ['title' => 'Activity 1 section 2']);
        $data->activity2_section1 = $perform_generator->create_section($data->activity2, ['title' => 'Activity 2 section 1']);
        // Section without relationship.
        $data->activity2_section2 = $perform_generator->create_section($data->activity2, ['title' => 'Activity 2 section 2']);

        // Two relationships for activity 1, section 1
        $data->activity1_section1_relationship1 = $perform_generator->create_section_relationship(
            $data->activity1_section1,
            ['relationship' => constants::RELATIONSHIP_APPRAISER]
        );
        $data->activity1_section1_relationship2 = $perform_generator->create_section_relationship(
            $data->activity1_section1,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        // Participant section
        $subject_instance = $perform_generator->create_subject_instance(
            [
                'activity_id'       => $data->activity1->id,
                'subject_user_id'   => $user1->id,
                'include_questions' => false,
            ]
        );
        $participant_instance = $perform_generator->create_participant_instance(
            $user1, $subject_instance->id, $data->activity1_section1_relationship1->id
        );

        $data->activity1_section_1_participant1 = $perform_generator->create_participant_section(
            $data->activity1, $participant_instance, false, $data->activity1_section1
        );

        // One relationship for activity 1, section 2
        $data->activity1_section2_relationship1 = $perform_generator->create_section_relationship(
            $data->activity1_section2,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );

        // One relationship for activity 2's first section.
        $data->activity2_section1_relationship1 = $perform_generator->create_section_relationship(
            $data->activity2_section1,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );

        return $data;
    }

}