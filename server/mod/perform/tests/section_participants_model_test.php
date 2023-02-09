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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;
use mod_perform\constants;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\expand_task;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\section_participants;
use mod_perform\task\service\subject_instance_creation;
use totara_job\job_assignment;

/**
 * @coversDefaultClass \mod_perform\models\response\section_participants
 *
 * @group perform
 */
class mod_perform_section_participants_model_testcase extends advanced_testcase {
    /**
     * @covers ::create_from_subject_instance
     */
    public function test_by_subject(): void {
        $section_count = 3;
        [$sections, $subject, $relationships] = $this->create_test_data($section_count);

        $mappings = section_participants::create_from_subject_instance($subject);
        $this->assertEquals($section_count, $mappings->count(), 'wrong count');

        foreach ($mappings as $mapping) {
            $section = $sections->item($mapping->get_section()->id) ?? null;
            $this->assertNotNull($section, 'unknown section in mapping');

            $participant_sections = $mapping->get_participant_sections();
            $this->assertEquals(
                $relationships->count(),
                $participant_sections->count(),
                'wrong participant instance count'
            );

            foreach ($participant_sections as $participant_section) {
                $this->assertInstanceOf(participant_section::class, $participant_section);
                $user_id = $participant_section->participant_instance->participant_id;
                $relationship = $participant_section->participant_instance->core_relationship->idnumber;

                $expected = $relationships->item($user_id) ?? null;
                $this->assertNotNull($expected, 'unknown participant in mapping');
                $this->assertEquals($expected, $relationship,'wrong relationship');
            }
        }
    }

    /**
     * Generates test data.
     *
     * @param int $no_of_sections no of sections to generate.
     *
     * @return array (mapping of activity sections by id, subject instance,
     *         mapping of user ids to relationship names) tuple.
     */
    private function create_test_data(int $no_of_sections): array {
        $this->setAdminUser();

        $base_generator = $this->getDataGenerator();
        $appr = $base_generator->create_user();
        $mgr = $base_generator->create_user();
        $mgr_ja = job_assignment::create(['userid' => $mgr->id, 'idnumber' => 'mgr']);

        $subject = $base_generator->create_user();
        $subject_ja = job_assignment::create(
            [
                'userid' => $subject->id,
                'idnumber' => 'subject',
                'managerjaid' => $mgr_ja->id,
                'appraiserid' => $appr->id,
            ]
        );

        $cohort = $base_generator->create_cohort();
        cohort_add_member($cohort->id, $subject->id);

        /** @var \mod_perform\testing\generator $generator */
        $generator = $base_generator->get_plugin_generator('mod_perform');
        $activity = $generator->create_activity_in_container(
            [
                'create_section' => false,
                'create_track' => true
            ]
        );

        $generator->create_track_assignments_with_existing_groups(
            $activity->tracks->first(),
            [$cohort->id]
        );

        $relationships = [
            constants::RELATIONSHIP_SUBJECT => $subject,
            constants::RELATIONSHIP_MANAGER => $mgr,
            constants::RELATIONSHIP_APPRAISER => $appr
        ];

        $sections = collection::new([]);
        for ($i = 0; $i < $no_of_sections; $i++) {
            $section = $generator->create_section($activity, ['title' => "section#$i"]);
            $sections->set($section, $section->id);

            foreach (array_keys($relationships) as $key => $relationship) {
                $generator->create_section_relationship($section, ['relationship' => $relationship]);
                section_element::create($section, $generator->create_element(), $key + 1);
            }
        }

        expand_task::create()->expand_all();
        (new subject_instance_creation())->generate_instances();

        $subject_instance = subject_instance_entity::repository()
            ->where('subject_user_id', $subject_ja->userid)
            ->get()
            ->map_to(subject_instance::class)
            ->first();

        $users_by_relationships = collection::new([]);
        foreach ($relationships as $relationship => $user) {
            $users_by_relationships->set($relationship, $user->id);
        }

        return [$sections, $subject_instance, $users_by_relationships];
    }
}
