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

use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\event\track_user_group_assigned;
use mod_perform\event\track_user_group_unassigned;

use mod_perform\user_groups\grouping;

/**
 * @coversDefaultClass \mod_perform\models\activity\track
 *
 * @group perform
 */
class mod_perform_track_model_group_assignments_testcase extends advanced_testcase {
    /**
     * @covers ::create
     * @covers ::add_assignment
     */
    public function test_create_track_assignments(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \totara_hierarchy\testing\generator $hierarchies */
        $hierarchies = $generator->get_plugin_generator('totara_hierarchy');
        $pos_fw_id = ['frameworkid' => $hierarchies->create_pos_frame([])->id];
        $org_fw_id = ['frameworkid' => $hierarchies->create_org_frame([])->id];
        $pos_id = $hierarchies->create_pos($pos_fw_id)->id;
        $org_id = $hierarchies->create_org($org_fw_id)->id;

        $cohort_id = $generator->create_cohort()->id;
        $user_id = $generator->create_user()->id;

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(['create_track' => true]);

        // Note: creating an activity creates a "default" track; so can make use
        // of that.
        $existing_tracks = $activity->get_tracks();
        $this->assertEquals(1, $existing_tracks->count(), 'wrong retrieved track count');

        $expected_assignments = [
            $cohort_id => [track_assignment_type::ADMIN, grouping::COHORT],
            $user_id => [track_assignment_type::ADMIN, grouping::USER],
            $pos_id => [track_assignment_type::ADMIN, grouping::POS],
            $org_id => [track_assignment_type::ADMIN, grouping::ORG]
        ];

        $track = $existing_tracks->first();
        foreach ($expected_assignments as $grp_id => $tuple) {
            [$assignment_type, $group_type] = $tuple;
            $group = grouping::by_type($group_type, $grp_id);

            $track->add_assignment($assignment_type, $group);
        }

        $actual_assignments = $track->assignments;
        $this->assertEquals(
            count($expected_assignments),
            $actual_assignments->count(),
            'wrong count'
        );

        foreach ($actual_assignments->all() as $assignment) {
            $group = $assignment->group;
            $grp_id = $group->get_id();
            $expected_values = $expected_assignments[$grp_id] ?? null;
            $this->assertNotNull($expected_values, "unknown assignment group id '$grp_id'");

            $actual_values = [$assignment->type, $group->get_type()];
            $this->assertEquals($expected_values, $actual_values, 'wrong assignment values');
            $this->assertEquals($track->id, $assignment->track_id, 'wrong track');
        }

        // Confirm the repository really has the new assignments.
        $existing_tracks = track::load_by_activity($activity);
        $this->assertEquals(1, $existing_tracks->count(), 'wrong retrieved track count');

        $retrieved_count = $existing_tracks->first()->assignments->count();
        $this->assertEquals(count($expected_assignments), $retrieved_count, 'wrong count');
    }

    /**
     * @covers ::create
     * @covers ::add_assignment
     */
    public function test_add_duplicate_track_assignments(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $cohort_id = $generator->create_cohort()->id;

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container(['create_track' => true]);

        $admin_type = track_assignment_type::ADMIN;
        $new_group = grouping::cohort($cohort_id);
        $track = track::load_by_activity($activity)
            ->first()
            ->add_assignment($admin_type, $new_group);

        $assignments = $track->assignments;
        $this->assertEquals(1, $assignments->count(), 'wrong count');

        $group = $assignments->first()->group;
        $this->assertEquals($cohort_id, $group->get_id(), 'wrong grp id');
        $this->assertEquals(grouping::COHORT, $group->get_type(), 'wrong type');

        // Try adding an identical assignment again.
        $assignments = $track
            ->add_assignment($admin_type, $new_group)
            ->assignments;
        $this->assertEquals(1, $assignments->count(), 'wrong count');

        $group = $assignments->first()->group;
        $this->assertEquals($cohort_id, $group->get_id(), 'wrong grp id');
        $this->assertEquals(grouping::COHORT, $group->get_type(), 'wrong type');
    }

    /**
     * @covers ::create
     * @covers ::remove_assignment
     */
    public function test_remove_track_assignments(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container(['create_track' => true]);

        // Note: creating an activity creates a "default" track; so can make use
        // of that.
        $existing_tracks = track::load_by_activity($activity);
        $this->assertEquals(1, $existing_tracks->count(), 'wrong retrieved track count');

        $track = $existing_tracks->first();
        $assignment_count = $generator
            ->create_track_assignments($track, 2, 2, 2, 2)
            ->assignments
            ->count();
        $this->assertEquals(8, $assignment_count, 'wrong assignment count');

        // Confirm the repository really has the new assignments.
        $assignment_count = track::load_by_activity($activity)
            ->first()
            ->assignments
            ->count();
        $this->assertEquals(8, $assignment_count, 'wrong assignment count');

        // Now delete assignments.
        $assignments = $track->assignments
            ->reduce(
                function (track $track, track_assignment $assignment): track {
                    return $track->remove_assignment($assignment->type, $assignment->group);
                },
                $track
            )
            ->assignments
            ->all();

        $this->assertEmpty($assignments, 'wrong assignment count');

        // Confirm the repository really does not have the assignments.
        $assignments = track::load_by_activity($activity)
            ->first()
            ->assignments
            ->all();
        $this->assertEmpty($assignments, 'wrong assignment count');
    }

    /**
     * @covers ::create
     * @covers ::remove_assignment
     */
    public function test_remove_duplicate_track_assignments(): void {
        $this->setAdminUser();
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container();
        $track = $generator->create_single_activity_track_and_assignment($activity);

        $assignments = $track->assignments;
        $this->assertEquals(1, $assignments->count(), 'wrong count');

        $assignment = $assignments->first();

        // Remove assignment, then try removing it again.
        $assignment_count = $track
            ->remove_assignment($assignment->type, $assignment->group)
            ->assignments
            ->count();
        $this->assertEquals(0, $assignment_count, 'wrong count');

        $assignment_count = $track
            ->remove_assignment($assignment->type, $assignment->group)
            ->assignments
            ->count();
        $this->assertEquals(0, $assignment_count, 'wrong count');
    }

    /**
     * @covers ::create
     * @covers ::remove_assignment
     */
    public function test_assignment_events(): void {
        $this->setAdminUser();
        $activity_admin = get_admin();

        $generator = $this->getDataGenerator();
        $hierarchies = $generator->get_plugin_generator('totara_hierarchy');
        $pos_fw_id = ['frameworkid' => $hierarchies->create_pos_frame([])->id];
        $org_fw_id = ['frameworkid' => $hierarchies->create_org_frame([])->id];
        $pos_id = $hierarchies->create_pos($pos_fw_id)->id;
        $org_id = $hierarchies->create_org($org_fw_id)->id;

        $cohort_id = $generator->create_cohort()->id;
        $user_id = $generator->create_user()->id;

        $groups = [
            $cohort_id => grouping::by_type(grouping::COHORT, $cohort_id),
            $user_id => grouping::by_type(grouping::USER, $user_id),
            $pos_id => grouping::by_type(grouping::POS, $pos_id),
            $org_id => grouping::by_type(grouping::ORG, $org_id)
        ];

        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(['create_track' => true]);
        $track = $activity->get_tracks()->first();

        $sink = $this->redirectEvents();
        foreach ($groups as $group) {
            $track->add_assignment(track_assignment_type::ADMIN, $group);
        }

        $events = $sink->get_events();
        $this->assertCount(count($groups), $events);

        foreach ($events as $event) {
            $this->assertInstanceOf(track_user_group_assigned::class, $event);

            $expected = $groups[$event->objectid] ?? null;
            $this->assertNotNull($expected, "Unknown user group id: '$event->objectid'");
            $this->assertEquals($activity_admin->id, $event->userid, 'wrong user id');
            $this->assertEquals($track->id, $event->other['track_id'], 'wrong track id');
        }

        $sink->clear();
        foreach ($groups as $group) {
            $track->remove_assignment(track_assignment_type::ADMIN, $group);
        }

        $events = $sink->get_events();
        $this->assertCount(count($groups), $events);

        foreach ($events as $event) {
            $this->assertInstanceOf(track_user_group_unassigned::class, $event);

            $expected = $groups[$event->objectid] ?? null;
            $this->assertNotNull($expected, "Unknown user group id: '$event->objectid'");
            $this->assertEquals($activity_admin->id, $event->userid, 'wrong user id');
            $this->assertEquals($track->id, $event->other['track_id'], 'wrong track id');
        }

        $sink->close();
    }
}
