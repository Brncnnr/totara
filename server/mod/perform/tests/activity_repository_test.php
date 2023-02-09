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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\state\activity\draft;
use mod_perform\state\activity\active;

/**
 * @group perform
 */
class mod_perform_activity_respository_testcase extends advanced_testcase {

    private const SINGLE_SECTION_ALL_RESPONDING = 'Single section all responding';
    private const SINGLE_SECTION_SOME_RESPONDING = 'Single section some responding';
    private const MULTIPLE_SECTION_ALL_RESPONDING = 'Multiple section all responding';
    private const MULTIPLE_SECTION_ALL_RESPONDING_ALL_SECTIONS = 'Multiple section all responding all sections';
    private const MULTIPLE_SECTION_SOME_RESPONDING = 'Multiple section some responding';

    /**
     * @param array $section1_relationship_maps
     * @param array $section2_relationship_maps
     * @param array $expected_relationship_id_numbers
     * @dataProvider get_responding_relationships_provider
     */
    public function test_get_responding_relationships(
        array $section1_relationship_maps,
        array $section2_relationship_maps,
        array $expected_relationship_id_numbers
    ): void {
        $subject_instance = $this->set_up_section_relationship_data($section1_relationship_maps, $section2_relationship_maps);

        $responding_relationships = activity_entity::repository()->get_responding_relationships($subject_instance->activity()->id);

        $actual_id_numbers = $responding_relationships->pluck('idnumber');

        self::assertEqualsCanonicalizing($expected_relationship_id_numbers, $actual_id_numbers);
    }

    private function set_up_section_relationship_data(array ...$section_relationship_maps): subject_instance {
        self::setAdminUser();

        $subject_user = self::getDataGenerator()->create_user();

        $subject_instance = $this->get_perform_generator()->create_subject_instance(
            [
                'activity_name'            => 'anonymous activity',
                'subject_is_participating' => false, // The subject actually is participating, but we will create the instance below.
                'subject_user_id'          => $subject_user->id,
                'other_participant_id'     => null,
                'include_questions'        => false,
                'anonymous_responses'      => 'true',
            ]
        );

        $activity = new activity($subject_instance->activity());

        foreach ($section_relationship_maps as $i => $section_relationship_map) {
            $section = $this->get_perform_generator()->create_section($activity, ['title' => "Part {$i}"]);

            foreach ($section_relationship_map as $relationship => $can_answer) {
                $this->get_perform_generator()->create_section_relationship(
                    $section,
                    ['relationship' => $relationship],
                    true,
                    $can_answer
                );
            }
        }

        return $subject_instance;
    }

    public function get_responding_relationships_provider(): array {
        return [
            self::SINGLE_SECTION_ALL_RESPONDING => [
                [
                    constants::RELATIONSHIP_SUBJECT => true,
                    constants::RELATIONSHIP_MANAGER => true,
                    constants::RELATIONSHIP_APPRAISER => true
                ],
                [],
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER],
            ],
            self::SINGLE_SECTION_SOME_RESPONDING => [
                [
                    constants::RELATIONSHIP_SUBJECT => true,
                    constants::RELATIONSHIP_MANAGER => true,
                    constants::RELATIONSHIP_APPRAISER => false
                ],
                [],
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER],
            ],
            self::MULTIPLE_SECTION_ALL_RESPONDING => [
                [
                    constants::RELATIONSHIP_SUBJECT => true,
                    constants::RELATIONSHIP_APPRAISER => false
                ],
                [
                    constants::RELATIONSHIP_MANAGER => true,
                    constants::RELATIONSHIP_APPRAISER => true
                ],
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER],
            ],
            self::MULTIPLE_SECTION_ALL_RESPONDING_ALL_SECTIONS => [
                [
                    constants::RELATIONSHIP_SUBJECT => true,
                    constants::RELATIONSHIP_MANAGER => true,
                    constants::RELATIONSHIP_APPRAISER => true
                ],
                [
                    constants::RELATIONSHIP_SUBJECT => true,
                    constants::RELATIONSHIP_MANAGER => true,
                    constants::RELATIONSHIP_APPRAISER => true
                ],
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER],
            ],
            self::MULTIPLE_SECTION_SOME_RESPONDING => [
                [
                    constants::RELATIONSHIP_SUBJECT => true,
                    constants::RELATIONSHIP_MANAGER => false,
                    constants::RELATIONSHIP_APPRAISER => false
                ],
                [
                    constants::RELATIONSHIP_SUBJECT => false,
                    constants::RELATIONSHIP_MANAGER => true,
                    constants::RELATIONSHIP_APPRAISER => false
                ],
                [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER],
            ],
        ];
    }

    private function get_perform_generator(): \mod_perform\testing\generator {
        return \mod_perform\testing\generator::instance();
    }

    public function test_filter_by_not_draft() {
        self::setAdminUser();
        $generator = $this->get_perform_generator();

        $draft_activity = $generator->create_activity_in_container(['activity_status' => draft::get_code()]);
        $active_activity = $generator->create_activity_in_container(['activity_status' => active::get_code()]);

        // Should return both.
        $this->assertEquals(2, activity_entity::repository()->count());

        $result = activity_entity::repository()->filter_by_not_draft()->get();
        // Should return one.
        $this->assertCount(1, $result);
        $activity = $result->first();
        // Should return the active one.
        $this->assertEquals($active_activity->id, $activity->id);
    }
}