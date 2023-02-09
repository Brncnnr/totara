<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\helpers\activity_multisection_toggler;
use mod_perform\state\activity\draft;

/**
 * @coversDefaultClass \mod_perform\models\activity\helpers\activity_multisection_toggler
 *
 * @group perform
 */
class mod_perform_activity_multisection_toggler_testcase extends advanced_testcase {
    /**
     * @covers ::set
     */
    public function test_toggle_single_to_single(): void {
        $element_count = 3;
        $activity = $this->setup_env(1, $element_count);

        $sections = $activity->sections;
        $this->assertCount(1, $sections->all(), 'wrong section count');
        $relationships = $sections->first()->get_section_relationships();
        $this->assertNotEmpty($relationships, 'no relationships created');

        $this->assert_multisection_setting($activity, false);
        $toggler = new activity_multisection_toggler($activity);
        $toggler->set(false);
        $this->assert_multisection_setting($activity, false);

        $sections = $activity->sections;
        $this->assertCount(1, $sections->all(), 'wrong section count');
        $this->assertCount(
            $element_count,
            $sections->first()->section_elements,
            'wrong element count'
        );

        $this->assertEqualsCanonicalizing(
            $relationships,
            $sections->first()->get_section_relationships(),
            'wrong relationships'
        );
    }

    /**
     * @covers ::set
     */
    public function test_toggle_single_to_multiple(): void {
        $element_count = 3;
        $activity = $this->setup_env(1, $element_count);
        $first_section_title = $activity->sections->first()->title;

        $sections = $activity->sections;
        $this->assertCount(1, $sections->all(), 'wrong section count');
        $this->assertNotEmpty(
            $sections->first()->get_section_relationships(),
            'no relationships created'
        );

        $this->assert_multisection_setting($activity, false);
        $toggler = new activity_multisection_toggler($activity);
        $toggler->set(true);
        $this->assert_multisection_setting($activity, true);

        $additional_sections = 3;
        $additional_elements_count = 2;
        $activity = $this->add_section_and_elements(
            $activity,
            $additional_sections,
            $additional_elements_count
        );

        $sections = $activity->sections;
        $this->assertCount($additional_sections + 1, $sections->all(), 'wrong count');

        foreach ($sections as $section) {
            $expected_count = $section->title === $first_section_title
                ? $element_count
                : $additional_elements_count;

            $this->assertCount($expected_count, $section->section_elements, 'wrong count');
            $this->assertNotEmpty($section->get_section_relationships(), 'no relationships');

            // Checking that the sort order is set correctly, should start with 1
            $i = 1;
            foreach ($section->section_elements as $element) {
                $this->assertEquals($i, $element->sort_order);
                $i++;
            }
        }
    }

    /**
     * @covers ::set
     */
    public function test_toggle_multiple_to_multiple(): void {
        $section_count = 3;
        $element_count = 2;
        $activity = $this->setup_env($section_count, $element_count);

        $this->assert_multisection_setting($activity, true);
        $toggler = new activity_multisection_toggler($activity);
        $toggler->set(true);
        $this->assert_multisection_setting($activity, true);

        $sections = $activity->sections;
        $this->assertCount($section_count, $sections->all(), 'wrong count');

        foreach ($sections as $section) {
            $this->assertCount($element_count, $section->section_elements, 'wrong count');
            $this->assertNotEmpty($section->get_section_relationships(), 'no relationships');

            // Checking that the sort order is set correctly, should start with 1
            $i = 1;
            foreach ($section->section_elements as $element) {
                $this->assertEquals($i, $element->sort_order);
                $i++;
            }
        }
    }

    /**
     * @covers ::set
     */
    public function test_toggle_multiple_to_single(): void {
        $section_count = 3;
        $element_count = 2;
        $activity = $this->setup_env($section_count, $element_count);

        // Multisection on -> off; everything should collapse into one section
        // with no section title and all participants removed. Also, the section
        // questions should be put into the single section according to the sort
        // order of the sections/elements at the time of the merge.
        $activity = $this->reverse_section_sort_order($activity);

        $sections = $activity->sections;
        $this->assertCount($section_count, $sections->all(), 'wrong count');

        $expected_element_order = [];
        $expected_element_count = $section_count * $element_count;

        $actual_section_element_count = 0;
        foreach ($sections as $section) {
            $this->assertNotEmpty($section->title, 'wrong title');
            $this->assertNotEmpty($section->section_relationships, 'no relationships');

            $tags = $section->section_elements
                ->sort('sort_order')
                ->transform(
                    function (section_element $section_element): string {
                        return $section_element->element->title;
                    }
                )
                ->all();

            $actual_section_element_count += count($tags);
            $expected_element_order = array_merge($expected_element_order, $tags);
        }

        $this->assertEquals(
            $expected_element_count,
            $actual_section_element_count,
            'wrong section element count'
        );

        $this->assert_multisection_setting($activity, true);
        $toggler = new activity_multisection_toggler($activity);
        $toggler->set(false);
        $this->assert_multisection_setting($activity, false);

        $sections = $activity->sections;
        $this->assertCount(1, $sections->all(), 'wrong count');

        $merged_section = $sections->first();
        $this->assertEmpty($merged_section->title, 'wrong title');
        $this->assertEmpty($merged_section->section_relationships, 'has relationships');

        $section_elements = $merged_section->section_elements;
        $this->assertCount(
            $expected_element_count,
            $section_elements,
            'wrong element count'
        );

        // Checking that the sort order is set correctly, should start with 1
        $i = 1;
        foreach ($section_elements as $element) {
            $this->assertEquals($i, $element->sort_order);
            $i++;
        }

        $actual_element_order = $section_elements
            ->sort('sort_order')
            ->transform(
                function (section_element $section_element): string {
                    return $section_element->element->title;
                }
            )
            ->all();

        $this->assertEquals($expected_element_order, $actual_element_order, 'wrong order');
    }

    /**
     * @covers ::set
     */
    public function test_toggle_with_one_section(): void {
        $element_count = 3;
        $activity = $this->setup_env(1, $element_count);
        $this->assertNotEmpty(
            $activity->sections->first()->get_section_relationships(),
            'no relationships created'
        );

        $this->assert_multisection_setting($activity, false);
        $toggler = new activity_multisection_toggler($activity);
        $toggler->set(true);
        $this->assert_multisection_setting($activity, true);

        $sections = $activity->sections;
        $this->assertCount(1, $sections->all(), 'wrong section count');
        $this->assertCount(
            $element_count,
            $sections->first()->section_elements,
            'wrong element count'
        );
        $this->assertNotEmpty(
            $sections->first()->get_section_relationships(),
            'no relationships'
        );

        $toggler->set(false);
        $this->assert_multisection_setting($activity, false);

        $sections = $activity->sections;
        $this->assertCount(1, $sections->all(), 'wrong section count');
        $this->assertCount(
            $element_count,
            $sections->first()->section_elements,
            'wrong element count'
        );
        $this->assertEmpty(
            $sections->first()->get_section_relationships(),
            'relationships remain'
        );
    }

    /**
     * @covers ::set
     */
    public function test_toggling_active_activity_is_not_possible(): void {
        $element_count = 3;
        $activity = $this->setup_env(1, $element_count);

        $activity->activate();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can\'t toggle multisection on an active activity.');

        $toggler = new activity_multisection_toggler($activity);
        $toggler->set(true);
    }

    /**
     * @covers ::get_current_setting
     */
    public function test_get_current_setting(): void {
        $activity = $this->setup_env();
        $toggler = new activity_multisection_toggler($activity);

        $this->assertCount(1, $activity->sections->all(), 'wrong section count');
        $this->assertFalse($toggler->get_current_setting(), 'wrong setting');
        $this->assert_multisection_setting($activity, false);

        // Deliberately add sections and make things inconsistent with settings.
        $activity = $this->add_section_and_elements($activity, 1, 1);
        $this->assertCount(2, $activity->sections->all(), 'wrong section count');
        $this->assertTrue($toggler->get_current_setting(), 'wrong setting');
        $this->assert_multisection_setting($activity, true);

        // Directly make settings inconsistent with section count.
        $activity->settings->update([activity_setting::MULTISECTION => false]);
        $this->assertCount(2, $activity->sections->all(), 'wrong section count');
        $this->assertTrue($toggler->get_current_setting(), 'wrong setting');
        $this->assert_multisection_setting($activity, true);

        // Once multisection is enabled, it remains even if there is only one
        // section.
        $activity->sections->first()->delete();
        $this->assertCount(1, $activity->refresh(true)->sections->all(), 'wrong section count');
        $this->assertTrue($toggler->get_current_setting(), 'wrong setting');
        $this->assert_multisection_setting($activity, true);

        // Directly make settings back to single. This sticks only if there is
        // one section.
        $activity->settings->update([activity_setting::MULTISECTION => false]);
        $this->assertCount(1, $activity->sections->all(), 'wrong section count');
        $this->assertFalse($toggler->get_current_setting(), 'wrong setting');
        $this->assert_multisection_setting($activity, false);
    }

    /**
     * Checks that activity's multisection setting is correct.
     *
     * @param activity $activity target activity.
     * @param bool $expected expect setting.
     */
    private function assert_multisection_setting(activity $activity, bool $expected): void {
        $actual = (bool)$activity
            ->settings
            ->lookup(activity_setting::MULTISECTION, false);

        $this->assertEquals($expected, $actual, 'wrong multisection setting');
    }

    /**
     * Creates sections with elements for the specified activity.
     *
     * @param activity $activity target activity.
     * @param int $no_of_sections no of sections for an activity.
     * @param int $elements_per_section no of section elements to generate.
     *
     * @return activity the refreshed activity.
     */
    private function add_section_and_elements(
        activity $activity,
        int $no_of_sections,
        int $elements_per_section
    ): activity {
        $generator = $this->generator();

        for ($i = 0; $i < $no_of_sections; $i++) {
            $section_title = $activity->name . ' new section #$i';
            $section = $generator->create_section($activity, ['title' => $section_title]);

            for ($j = 1; $j <= $elements_per_section; $j++) {
                $title = $section->title . " element: #$j";

                $element = $generator->create_element(['title' => $title]);
                section_element::create($section, $element, $j);
            }

            $generator->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_SUBJECT]);
        }

        return $activity->refresh(true);
    }

    /**
     * Reverses the sort order of the given activity's sections/section elements.
     *
     * @param activity $activity the activity whose section sorting orders are
     *        to be reversed.
     *
     * @return activity the updated activity.
     */
    private function reverse_section_sort_order(activity $activity): activity {
        $reversed_sections = $activity->sections->sort('sort_order', 'desc');

        $section_sort_order = 1;
        foreach ($reversed_sections as $section) {
            $entity = new section_entity($section->id);
            $entity->sort_order = $section_sort_order++;
            $entity->save();

            $element_sort_order = 1;
            $reversed_elements = $section->section_elements->sort('sort_order', 'desc');

            /** @var section_element $element */
            foreach ($reversed_elements as $element) {
                $element->update_sort_order(-$element_sort_order++);
            }

            foreach ($reversed_elements as $element) {
                $element->update_sort_order(-$element->sort_order);
            }
        }

        return $activity->refresh(true);
    }

    /**
     * Generates test data.
     *
     * @param int $no_of_sections no of sections for an activity.
     * @param int $elements_per_section no of section elements to generate.
     *
     * @return activity the created activity.
     */
    private function setup_env(
        int $no_of_sections = 1,
        int $elements_per_section = 1
    ): activity {
        $this->setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_activity_status(draft::get_code())
            ->set_number_of_activities(1)
            ->set_number_of_sections_per_activity($no_of_sections)
            ->set_number_of_elements_per_section($elements_per_section)
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT])
            ->disable_user_assignments()
            ->disable_subject_instances();

        /** @var activity $activity */
        $activity = $this->generator()
            ->create_full_activities($configuration)
            ->first();

        $activity->settings->update(
            [
                activity_setting::MULTISECTION => ($no_of_sections > 1)
            ]
        );

        return $activity;
    }

    /**
     * Gets the generator instance
     *
     * @return \mod_perform\testing\generator
     */
    private function generator(): \mod_perform\testing\generator {
        return \mod_perform\testing\generator::instance();
    }
}