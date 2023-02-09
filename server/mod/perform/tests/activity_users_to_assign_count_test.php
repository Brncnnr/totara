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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_assignment;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\activity;
use mod_perform\state\activity\draft;

/**
 * @group perform
 * @covers \mod_perform\models\activity\activity::get_users_to_assign_count
 */
class mod_perform_activity_users_to_assign_count_testcase extends advanced_testcase {

    /**
     * @var \mod_perform\testing\generator
     */
    protected $generator;

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->generator = null;
    }

    /**
     * Cover several different scenarios for returning the users that will be assigned to an activity.
     *
     * This is a single large test for performance reasons - there is a lot of data that is required to be set up.
     *
     * We are specifically checking that:
     *   - The count is correct for a specific activity
     *   - The 'user' user group is handled correctly
     *   - Multiple groups of the same type are counted correctly and don't result in duplicates being counted
     *   - Multiple groups of the different types are counted correctly and don't result in duplicates being counted
     *   - An exception is thrown if counting when the activity is active (as then the count would be unnecessary and inaccurate
     *
     * @covers \mod_perform\models\activity\activity::get_users_to_assign_count
     */
    public function test_get_users_to_assign_count() {
        [$cohort1, $cohort2, $pos1, $pos2, $org1, $org2, $shared_users] = $this->create_user_groups();

        // Make sure that different activities return the assignment count for their respective tracks.
        $activity1 = $this->create_activity_with_track_assignment([$cohort1->id]);
        $activity1_actual = $activity1->get_users_to_assign_count();

        $activity2 = $this->create_activity_with_track_assignment([$cohort2->id]);
        $activity2_actual = $activity2->get_users_to_assign_count();

        $this->expand_activity($activity1);
        $this->expand_activity($activity2);

        $activity1_expected = $this->get_assigned_user_count($activity1);
        $activity2_expected = $this->get_assigned_user_count($activity2);

        $this->assertNotEquals($activity1_expected, $activity2_expected);
        $this->assertEquals($activity1_expected, $activity1_actual);
        $this->assertEquals($activity2_expected, $activity2_actual);


        // Make sure directly assigned users are counted correctly.
        $activity3 = $this->create_activity_with_track_assignment([], [], [], $shared_users);
        $activity3_actual = $activity3->get_users_to_assign_count();
        $this->expand_activity($activity3);
        $this->assertEquals($this->get_assigned_user_count($activity3), $activity3_actual);


        // Make sure that multiple groups of the same type are counted correctly.
        $activity4 = $this->create_activity_with_track_assignment([$cohort1->id, $cohort2->id]);
        $activity4_actual = $activity4->get_users_to_assign_count();
        $this->expand_activity($activity4);
        $activity4_expected = $this->get_assigned_user_count($activity4);
        $this->assertNotEquals($activity4_expected, $activity1_actual);
        $this->assertEquals($activity4_expected, $activity4_actual);


        // Make sure that single groups of multiple different types are counted correctly.
        $activity5 = $this->create_activity_with_track_assignment([$cohort1->id], [$org1->id], [$pos1->id]);
        $activity5_actual = $activity5->get_users_to_assign_count();
        $this->expand_activity($activity5);
        $activity5_expected = $this->get_assigned_user_count($activity5);

        $activity6 = $this->create_activity_with_track_assignment([$cohort2->id], [$org2->id], [$pos2->id]);
        $activity6_actual = $activity6->get_users_to_assign_count();
        $this->expand_activity($activity6);
        $activity6_expected = $this->get_assigned_user_count($activity6);

        $this->assertNotEquals($activity5_expected, $activity6_expected);
        $this->assertEquals($activity5_expected, $activity5_actual);
        $this->assertEquals($activity6_expected, $activity6_actual);


        // Make sure that multiple groups of multiple different types are counted correctly.
        $activity7 = $this->create_activity_with_track_assignment(
            [$cohort1->id, $cohort2->id], [$org1->id, $org1->id], [$pos1->id, $pos2->id], $shared_users
        );
        $activity7_actual = $activity7->get_users_to_assign_count();
        $this->expand_activity($activity7);
        $this->assertEquals($this->get_assigned_user_count($activity7), $activity7_actual);

        // Make sure null is returned when attempting to query this on an activity that isn't ready to be activated.
        $activity_not_ready = $this->generator()->create_activity_in_container(['activity_status' => draft::get_code()]);
        $this->assertNull($activity_not_ready->get_users_to_assign_count());

        // Make sure we get an exception when attempting to query this on an already activated activity.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Activity {$activity1->id} has already been activated");
        $activity1->get_users_to_assign_count();
    }

    /**
     * @return \mod_perform\testing\generator
     */
    private function generator(): \mod_perform\testing\generator {
        if (!isset($this->generator)) {
            $this->generator = \mod_perform\testing\generator::instance();
        }
        return $this->generator;
    }

    /**
     * Create many users and user groups to use for this test.
     *
     * @return array
     */
    private function create_user_groups(): array {
        $user_ids = [];
        for ($i = 1; $i < 37; $i++) {
            $user_ids[$i] = $this->getDataGenerator()->create_user()->id;
        }

        $shared_users = [
            $user_ids[29],
            $user_ids[30],
            $user_ids[31],
            $user_ids[32],
            $user_ids[33],
            $user_ids[34],
            $user_ids[35],
            $user_ids[36],
        ];

        $cohort1_users = [
            $user_ids[1],
            $user_ids[2],
        ];
        $cohort1 = $this->generator()->create_cohort_with_users(array_merge($shared_users, $cohort1_users));

        $cohort2_users = [
            $user_ids[3],
            $user_ids[4],
            $user_ids[5],
        ];
        $cohort2 = $this->generator()->create_cohort_with_users(array_merge($shared_users, $cohort2_users));

        $org1_users = [
            $user_ids[6],
            $user_ids[7],
            $user_ids[8],
            $user_ids[9],
        ];
        $org1 = $this->generator()->create_organisation_with_users(array_merge($shared_users, $org1_users));

        $org2_users = [
            $user_ids[10],
            $user_ids[11],
            $user_ids[12],
            $user_ids[13],
            $user_ids[14],
        ];
        $org2 = $this->generator()->create_organisation_with_users(array_merge($shared_users, $org2_users));

        $pos1_users = [
            $user_ids[15],
            $user_ids[16],
            $user_ids[17],
            $user_ids[18],
            $user_ids[19],
            $user_ids[20],
        ];
        $pos1 = $this->generator()->create_position_with_users(array_merge($shared_users, $pos1_users));

        $pos2_users = [
            $user_ids[21],
            $user_ids[22],
            $user_ids[23],
            $user_ids[24],
            $user_ids[25],
            $user_ids[26],
            $user_ids[28],
        ];
        $pos2 = $this->generator()->create_position_with_users(array_merge($shared_users, $pos2_users));

        return [$cohort1, $cohort2, $pos1, $pos2, $org1, $org2, $shared_users];
    }

    /**
     * Create a draft activity that is able to be activated with tracks for the groups specified.
     *
     * @param array $cohorts
     * @param array $orgs
     * @param array $pos
     * @param array $users
     * @return activity
     */
    private function create_activity_with_track_assignment(array $cohorts = [], array $orgs = [],
                                                           array $pos = [], array $users = []): activity {
        // We need the activity to be a draft - since we are making the assigned count check before activation.
        $activity = $this->generator()->create_activity_in_container([
            'activity_status' => draft::get_code(),
            'create_section' => false,
            'create_track' => false
        ]);

        // Must create a section with an element and a relationship in order to allow an activity to be activated
        $section = $this->generator()->create_section($activity);
        $this->generator()->create_section_element(
            $section,
            $this->generator()->create_element()
        );
        $this->generator()->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_SUBJECT]);

        $track = $this->generator()->create_activity_tracks($activity)->first();
        $this->generator()->create_track_assignments_with_existing_groups($track, $cohorts, $orgs, $pos, $users);

        return $activity;
    }

    /**
     * Expand all the tracks for an activity.
     *
     * @param activity $activity
     */
    private function expand_activity(activity $activity): void {
        $activity->activate();

        $track_assignments = track_assignment::repository()
            ->select('id')
            ->join([track_entity::TABLE, 'track'], 'track_id', 'id')
            ->where('track.activity_id', $activity->id)
            ->get()
            ->pluck('id');

        expand_task::create()->expand_multiple($track_assignments);
    }

    /**
     * Get the number of unique users that actually have been assigned to an activity after the expand assignments task has run.
     *
     * @param activity $activity
     * @return int
     */
    private function get_assigned_user_count(activity $activity): int {
        return track_user_assignment::repository()
            ->select_raw('DISTINCT subject_user_id')
            ->join([track::TABLE, 'track'], 'track_id', 'id')
            ->with('track')
            ->where('track.activity_id', $activity->id)
            ->count();
    }

}
