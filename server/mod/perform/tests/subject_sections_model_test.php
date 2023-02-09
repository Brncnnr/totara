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
use mod_perform\expand_task;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\response\subject_sections;
use mod_perform\task\service\subject_instance_creation;
use totara_job\job_assignment;

/**
 * @coversDefaultClass \mod_perform\models\response\subject_sections
 *
 * @group perform
 */
class mod_perform_subject_sections_model_testcase extends advanced_testcase {
    /**
     * @covers ::create_from_subject_instances
     */
    public function test_by_subjects(): void {
        $activity_count = 3;
        $section_count = 2;
        [$sections, $subjects] = $this->create_test_data($activity_count, $section_count);

        $mappings = subject_sections::create_from_subject_instances($subjects);
        $this->assertEquals($activity_count, $mappings->count(), 'wrong count');

        foreach ($mappings as $mapping) {
            $subject = $subjects->item($mapping->get_subject_instance()->id) ?? null;
            $this->assertNotNull($subject, 'unknown section in mapping');

            $actual_sections = $mapping->get_sections();
            $this->assertEquals($section_count, $actual_sections->count(), 'wrong count');

            foreach ($actual_sections as $section_participants) {
                $section = $sections->item($section_participants->get_section()->id) ?? null;
                $this->assertNotNull($section, 'unknown section in mapping');
            }
        }
    }

    /**
     * Generates test data.
     *
     * @param int $no_of_activities no of activities (and subject instances) to
     *        generate.
     * @param int $no_of_sections no of sections to generate.
     *
     * @return array (mapping of activity sections by id, subject instances) tuple.
     */
    private function create_test_data(int $no_of_activities, int $no_of_sections): array {
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

        $relationships = [
            constants::RELATIONSHIP_SUBJECT => $subject,
            constants::RELATIONSHIP_MANAGER =>  $mgr,
            constants::RELATIONSHIP_APPRAISER => $appr
        ];

        $cohort = $base_generator->create_cohort();
        cohort_add_member($cohort->id, $subject->id);

        $sections = collection::new([]);
        for ($i =  0; $i < $no_of_activities; $i++) {
            $sections = $this->create_activity($sections, $cohort->id, array_keys($relationships), $no_of_sections);
        }

        expand_task::create()->expand_all();
        (new subject_instance_creation())->generate_instances();

        $subject_instances = subject_instance_entity::repository()
            ->where('subject_user_id', $subject_ja->userid)
            ->get()
            ->map_to(subject_instance::class)
            ->key_by('id');

        return [$sections, $subject_instances];
    }

    /**
     * Generates an activity.
     *
     * @param collection|section[] $sections mapping of existing section ids to
     *        sections.
     * @param int $cohort_id identifies the cohort to assign to the activity.
     * @param array $relationships list of relationships to use when
     *        assignment participants.
     * @param int $no_of_sections no of sections to generate.
     *
     * @return collection|section[] updated mapping of sections ids to sections.
     */
    private function create_activity(
        collection $sections,
        int $cohort_id,
        array $relationships,
        int $no_of_sections
    ): collection {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container(
            [
                'create_section' => false,
                'create_track' => true
            ]
        );

        $generator->create_track_assignments_with_existing_groups(
            $activity->tracks->first(),
            [$cohort_id]
        );

        for ($i = 0; $i < $no_of_sections; $i++) {
            $section = $generator->create_section($activity, ['title' => "section#$i"]);
            $sections->set($section, $section->id);

            foreach ($relationships as $key => $relationship) {
                $generator->create_section_relationship($section, ['relationship' => $relationship]);
                section_element::create($section, $generator->create_element(), $key + 1);
            }
        }

        return $sections;
    }
}
