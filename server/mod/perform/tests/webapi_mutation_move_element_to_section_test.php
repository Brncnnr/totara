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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\webapi\execution_context;
use mod_perform\models\activity\activity;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use mod_perform\webapi\resolver\mutation\move_element_to_section;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\remove_track_assignments
 *
 * @group perform
 */
class mod_perform_webapi_mutation_move_element_to_section_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_move_element_to_section';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_move_element_to_section(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(2)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(2)
            ->set_number_of_elements_per_section(2)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_section2 = $a1_sections->last();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();
        $a1_s1_sectionelement2 = $a1_s1_sectionelements->last();

        // Verify some data before we make changes.
        self::assertCount(2, $a1_s1_sectionelements);
        self::assertEquals(2, $a1_s1_sectionelement2->sort_order);

        // Move between sections.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => $a1_section1->id,
                'target_section_id' => $a1_section2->id,
            ],
        ];
        // Call the graphql query, to check that it is working.
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        self::assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);

        // The result contains the remaining element in section 1.
        self::assertCount(1, $result['source_section_elements']);
        $result_a1_s1_sectionelement2 = reset($result['source_section_elements']);
        self::assertEquals($a1_s1_sectionelement2->element->id, $result_a1_s1_sectionelement2['element']['id']);
        // The sort_order has been updated.
        self::assertEquals(2, $result_a1_s1_sectionelement2['sort_order']);

        // The target section now contains the element.
        $result_a1_s2_sectionelements = $a1_section2->section_elements;
        self::assertCount(3, $result_a1_s2_sectionelements);
        $result_a1_s2_sectionelement3 = $result_a1_s2_sectionelements->last();
        self::assertEquals($a1_s1_sectionelement1->element_id, $result_a1_s2_sectionelement3->element_id);
        self::assertEquals($a1_section2->id, $result_a1_s2_sectionelement3->section_id);
        self::assertEquals(3, $result_a1_s2_sectionelement3->sort_order);
    }

    public function test_move_element_to_section_fail_between_activities(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(2)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(1)
            ->set_number_of_elements_per_section(1)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();
        /** @var activity $activity2 */
        $activity2 = $activities->last();
        $a2_sections = $activity2->get_sections();
        $a2_section1 = $a2_sections->first();

        // Try to move between sections.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => $a1_section1->id,
                'target_section_id' => $a2_section1->id,
            ],
            'activity' => $activity1,
        ];
        $ec = execution_context::create('dev');
        self::expectExceptionMessage("Element cannot be moved to a section belonging to a different activity");
        move_element_to_section::resolve($args, $ec);
    }

    public function test_move_element_to_section_fail_within_same_section(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(1)
            ->set_number_of_elements_per_section(1)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();

        // Try to move within one section.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => $a1_section1->id,
                'target_section_id' => $a1_section1->id,
            ],
            'activity' => $activity1,
        ];
        $ec = execution_context::create('dev');
        self::expectExceptionMessage("Element must be moved to a section other than its current section");
        move_element_to_section::resolve($args, $ec);
    }

    public function test_move_element_to_section_fail_with_invalid_element(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(1)
            ->set_number_of_elements_per_section(1)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_section2 = $a1_sections->last();

        // Try to move within one section.
        $args = [
            'input' => [
                'element_id' => 123456,
                'source_section_id' => $a1_section1->id,
                'target_section_id' => $a1_section2->id,
            ],
            'activity' => $activity1,
        ];
        $ec = execution_context::create('dev');
        self::expectExceptionMessage("Element does not exist or does not belong to source section");
        move_element_to_section::resolve($args, $ec);
    }

    public function test_move_element_to_section_fail_with_invalid_source_section(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(2)
            ->set_number_of_elements_per_section(1)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_section2 = $a1_sections->last();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();

        // Try to move within one section.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => 123456,
                'target_section_id' => $a1_section2->id,
            ],
            'activity' => $activity1,
        ];
        $ec = execution_context::create('dev');
        self::expectExceptionMessage("Element does not exist or does not belong to source section");
        move_element_to_section::resolve($args, $ec);
    }

    public function test_move_element_to_section_fail_with_invalid_target_section(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(1)
            ->set_number_of_elements_per_section(1)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();

        // Try to move within one section.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => $a1_section1->id,
                'target_section_id' => 123456,
            ],
            'activity' => $activity1,
        ];
        $ec = execution_context::create('dev');
        self::expectExceptionMessage("Target section does not exist");
        move_element_to_section::resolve($args, $ec);
    }

    public function test_move_element_to_section_fail_with_mismatched_element_and_section(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(2)
            ->set_number_of_elements_per_section(1)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_section2 = $a1_sections->last();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();

        // Try to move within one section.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => $a1_section2->id,
                'target_section_id' => $a1_section1->id,
            ],
            'activity' => $activity1,
        ];
        $ec = execution_context::create('dev');
        self::expectExceptionMessage("Element does not exist or does not belong to source section");
        move_element_to_section::resolve($args, $ec);
    }

    public function test_move_element_to_section_fail_when_active(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_activity_status(active::get_code())
            ->set_number_of_sections_per_activity(2)
            ->set_number_of_elements_per_section(1)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_section2 = $a1_sections->last();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();

        // Try to move within one section.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => $a1_section1->id,
                'target_section_id' => $a1_section2->id,
            ],
            'activity' => $activity1,
        ];
        $ec = execution_context::create('dev');
        self::expectExceptionMessage("Element cannot be moved if activity is active");
        move_element_to_section::resolve($args, $ec);
    }

    public function test_missing_permission(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(2)
            ->set_activity_status(draft::get_code())
            ->set_number_of_sections_per_activity(2)
            ->set_number_of_elements_per_section(2)
            ->set_relationships_per_section([])
            ->set_cohort_assignments_per_activity(0);
        $activities = $generator->create_full_activities($config);

        /** @var activity $activity1 */
        $activity1 = $activities->first();
        $a1_sections = $activity1->get_sections();
        $a1_section1 = $a1_sections->first();
        $a1_section2 = $a1_sections->last();
        $a1_s1_sectionelements = $a1_section1->get_section_elements();
        $a1_s1_sectionelement1 = $a1_s1_sectionelements->first();

        // Move between sections.
        $args = [
            'input' => [
                'element_id' => $a1_s1_sectionelement1->element_id,
                'source_section_id' => $a1_section1->id,
                'target_section_id' => $a1_section2->id,
            ],
            'activity' => $activity1,
        ];

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }
}
