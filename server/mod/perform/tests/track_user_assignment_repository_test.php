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

use core_phpunit\testcase;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\track;
use mod_perform\models\activity\trigger\repeating\after_creation;

/**
 * @group perform
 */
class mod_perform_track_user_assignment_repository_testcase extends testcase {

    /**
     * @return \mod_perform\testing\generator
     */
    protected function generator() {
        return \mod_perform\testing\generator::instance();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_filter_by_possibly_has_subject_instances_to_create(): void {
        $generator = $this->generator();
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_sections_per_activity(0)
            ->set_number_of_users_per_user_group_type(4);
        /** @var activity_model $activity */
        $activity = $generator->create_full_activities($config)->first();
        /** @var track $track */
        $track = $activity->get_tracks()->first();

        $track_user_assignments = track_user_assignment::repository()->get();
        $this->assertCount(4, $track_user_assignments);
        /** @var subject_instance[] $subject_instances */
        $subject_instances = subject_instance::repository()->get()->all();
        $this->assertCount(4, $subject_instances);
        $subject_instance_1 = $subject_instances[0];
        $subject_instance_2 = $subject_instances[1];
        $subject_instance_3 = $subject_instances[2];
        $subject_instance_4 = $subject_instances[3];
        // #1: Leave just one instance.
        // #2: Add another instance.
        $this->add_subject_instance($subject_instance_2->track_user_assignment_id);
        // #3: Add two instances.
        $this->add_subject_instance($subject_instance_3->track_user_assignment_id);
        $this->add_subject_instance($subject_instance_3->track_user_assignment_id);
        // #4: Remove instance so we have one assignment without instances.
        $subject_instance_4->delete();

        // We expect just one result because by default the track has repeating off
        // and only the record without subject instance should be found.
        $track_user_assignments = track_user_assignment::repository()
            ->select('*')
            ->filter_by_possibly_has_subject_instances_to_create()
            ->get();
        $this->assertCount(1, $track_user_assignments);
        $assignment = $track_user_assignments->find('id', $subject_instance_4->track_user_assignment_id);
        $this->assertEquals(null, $assignment->subject_instance_count);

        // Turn repeating on. We expect results for all 4 assignments.
        $trigger = new after_creation();
        $offset = new date_offset(
            5,
            date_offset::UNIT_DAY
        );
        $track->set_repeating_enabled(
            track_entity::SCHEDULE_REPEATING_TYPE_UNSET,
            $offset,
            null,
            $trigger
        );
        $track->update();
        $track_user_assignments = track_user_assignment::repository()
            ->select('*')
            ->filter_by_possibly_has_subject_instances_to_create()
            ->get();
        $this->assertCount(4, $track_user_assignments);

        $assignment = $track_user_assignments->find('id', $subject_instance_1->track_user_assignment_id);
        $this->assertEquals(1, $assignment->subject_instance_count);

        $assignment = $track_user_assignments->find('id', $subject_instance_2->track_user_assignment_id);
        $this->assertEquals(2, $assignment->subject_instance_count);

        $assignment = $track_user_assignments->find('id', $subject_instance_3->track_user_assignment_id);
        $this->assertEquals(3, $assignment->subject_instance_count);

        $assignment = $track_user_assignments->find('id', $subject_instance_4->track_user_assignment_id);
        $this->assertEquals(null, $assignment->subject_instance_count);

        // Set repeat limit and check expected results.
        $offset = new date_offset(
            5,
            date_offset::UNIT_DAY
        );
        $track->set_repeating_enabled(
            track_entity::SCHEDULE_REPEATING_TYPE_UNSET,
            $offset,
            2,
            $trigger
        );
        $track->update();
        $track_user_assignments = track_user_assignment::repository()
            ->select('*')
            ->filter_by_possibly_has_subject_instances_to_create()
            ->get();
        $this->assertCount(2, $track_user_assignments);
        $this->assertNotNull($track_user_assignments->find('id', $subject_instance_1->track_user_assignment_id));
        $this->assertNotNull($track_user_assignments->find('id', $subject_instance_4->track_user_assignment_id));

        // Increase repeat limit and check expected results.
        $offset = new date_offset(
            5,
            date_offset::UNIT_DAY
        );
        $track->set_repeating_enabled(
            track_entity::SCHEDULE_REPEATING_TYPE_UNSET,
            $offset,
            3,
            $trigger
        );
        $track->update();
        $track_user_assignments = track_user_assignment::repository()
            ->select('*')
            ->filter_by_possibly_has_subject_instances_to_create()
            ->get();
        $this->assertCount(3, $track_user_assignments);
        $this->assertNotNull($track_user_assignments->find('id', $subject_instance_1->track_user_assignment_id));
        $this->assertNotNull($track_user_assignments->find('id', $subject_instance_2->track_user_assignment_id));
        $this->assertNotNull($track_user_assignments->find('id', $subject_instance_4->track_user_assignment_id));
    }

    /**
     * Create an additional subject instance for test data setup.
     *
     * @param int $track_user_assignment_id
     */
    private function add_subject_instance(int $track_user_assignment_id): void {
        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('id', $track_user_assignment_id)
            ->one();

        $subject_instance = new subject_instance();
        $subject_instance->track_user_assignment_id = $track_user_assignment_id;
        $subject_instance->subject_user_id = $track_user_assignment->subject_user_id;
        $subject_instance->save();
    }

}
