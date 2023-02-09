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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\section_relationship;
use mod_perform\models\activity\section_relationship as section_relationship_model;
use mod_perform\models\activity\section;
use mod_perform\state\activity\active;
use totara_core\entity\relationship;

abstract class mod_perform_relationship_testcase extends advanced_testcase {
    /**
     * @return \mod_perform\testing\generator
     */
    protected function perform_generator() {
        return \mod_perform\testing\generator::instance();
    }

    /**
     * @param stdClass|null $as_user
     * @param int|null $activity_status optional, defaults to active, see active::get_code()
     * @return object
     */
    protected function create_test_data(?stdClass $as_user = null, int $activity_status = null) {
        if ($as_user) {
            self::setUser($as_user);
        } else {
            self::setAdminUser();
        }

        if ($activity_status === null) {
            $activity_status = active::get_code();
        }

        $perform_generator = $this->perform_generator();

        $data = new stdClass();

        /*
         * Set up:
         *
         * activity1
         *   - section1_1
         *       - relationship1_1_1: appraiser
         *       - relationship1_1_2: manager
         *   - section1_2
         *       - relationship1_2_1: subject
         *
         * activity2
         *   - section2_1
         *       - relationship2_1_1: subject
         *   - section2_2
         *
         * activity3
         *   (no sections, no relationships)
         */
        $data->activity1 = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity 1',
            'activity_status' => $activity_status
        ]);
        $data->activity2 = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity 2',
            'activity_status' => $activity_status
        ]);
        $data->activity3 = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity 3',
            'activity_status' => $activity_status
        ]);

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

    /**
     * @param section $section
     * @param array $expected_relationships
     */
    protected function assert_section_relationships(section $section, array $expected_relationships): void {
        $actual_relationships = relationship::repository()
            ->select_raw('DISTINCT idnumber')
            ->join([section_relationship::TABLE, 'section_relationships'], 'id', 'core_relationship_id')
            ->where('section_relationships.section_id', $section->id)
            ->get()
            ->pluck('idnumber');

        $this->assertEqualsCanonicalizing($expected_relationships, $actual_relationships);
    }

    /**
     * Asserts the right can_view status is saved for each relationship in a section.
     *
     * @param section $section
     * @param array $relationships
     * @return void
     */
    protected function assert_can_view_and_answer_status(section $section, array $relationships): void {
        $section1_relationships = $section->get_section_relationships();

        foreach ($relationships as $relationship) {
            /** @var section_relationship $created_relationship */
            $created_relationship = $section1_relationships->find(
                function ($section_relationship) use ($relationship) {
                    return $relationship['core_relationship_id'] == $section_relationship->core_relationship_id;
                }
            );

            $this->assertInstanceOf(section_relationship_model::class, $created_relationship);

            $expected_can_view = $relationship['can_view'] ?? false;
            $this->assertEquals($expected_can_view, (bool) $created_relationship->can_view);

            $expected_can_answer = $relationship['can_answer'] ?? false;
            $this->assertEquals($expected_can_answer, (bool) $created_relationship->can_answer);
        }
    }
}