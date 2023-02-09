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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\track_assignment;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\user_groups\grouping;
use totara_cohort\event\members_updated;

/**
 * Tests covering the user group observer making sure the events do the right thing
 *
 * @group perform
 */
class mod_perform_assignment_audience_user_group_observer_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");
    }

    public function test_adding_cohort_member_sets_expand_flag() {
        $data = $this->prepare_assignments();

        // All expand flags are reset
        $this->assert_assignment_not_marked_for_expansion($data->assignment1->id);
        $this->assert_assignment_not_marked_for_expansion($data->assignment2->id);

        cohort_add_member($data->cohort1->id, $data->user1->id);

        $this->assert_assignment_marked_for_expansion($data->assignment1->id);
        $this->assert_assignment_not_marked_for_expansion($data->assignment2->id);

        cohort_add_member($data->cohort2->id, $data->user1->id);

        $this->assert_assignment_marked_for_expansion($data->assignment1->id);
        $this->assert_assignment_marked_for_expansion($data->assignment2->id);
    }

    public function test_removing_cohort_member_sets_expand_flag() {
        $data = $this->prepare_assignments();

        cohort_add_member($data->cohort1->id, $data->user1->id);

        expand_task::create()->expand_all();

        // All expand flags are reset
        $this->assert_assignment_not_marked_for_expansion($data->assignment1->id);
        $this->assert_assignment_not_marked_for_expansion($data->assignment2->id);

        // Now remove the user from the cohort
        cohort_remove_member($data->cohort1->id, $data->user1->id);

        $this->assert_assignment_marked_for_expansion($data->assignment1->id);
        $this->assert_assignment_not_marked_for_expansion($data->assignment2->id);
    }

    public function test_dynamic_cohort_update_event_sets_expand_flag() {
        $data = $this->prepare_assignments();

        // All expand flags are reset
        $this->assert_assignment_not_marked_for_expansion($data->assignment1->id);
        $this->assert_assignment_not_marked_for_expansion($data->assignment2->id);

        // It is a lot of work to set up a dynamic cohort so for simplicity we just trigger the event directly
        members_updated::create(['objectid' => $data->cohort1->id])->trigger();

        $this->assert_assignment_marked_for_expansion($data->assignment1->id);
        $this->assert_assignment_not_marked_for_expansion($data->assignment2->id);
    }

    public function test_deleting_cohort_removes_assignment() {
        $data = $this->prepare_assignments();

        cohort_add_member($data->cohort1->id, $data->user1->id);
        cohort_add_member($data->cohort2->id, $data->user2->id);

        expand_task::create()->expand_all();

        $this->assertTrue(track_assignment::repository()->where('user_group_id', $data->cohort1->id)->exists());
        $this->assertTrue(track_assignment::repository()->where('user_group_id', $data->cohort2->id)->exists());
        $this->assertEquals(
            2,
            track_user_assignment::repository()
                ->where('deleted', 0)
                ->count()
        );

        // Deleting the cohort will remove the assignment
        // and will mark all orphaned user assignments as deleted
        cohort_delete_cohort($data->cohort1);

        $this->assertFalse(track_assignment::repository()->where('user_group_id', $data->cohort1->id)->exists());
        $this->assertTrue(track_assignment::repository()->where('user_group_id', $data->cohort2->id)->exists());

        $this->assertEquals(
            1,
            track_user_assignment::repository()
                ->where('deleted', 1)
                ->where('subject_user_id', $data->user1->id)
                ->count()
        );

        $this->assertEquals(
            1,
            track_user_assignment::repository()
                ->where('deleted', 0)
                ->where('subject_user_id', $data->user2->id)
                ->count()
        );
    }

    protected function assert_assignment_not_marked_for_expansion(int $assignment_id) {
        $assignment_exists = track_assignment::repository()
            ->where('expand', false)
            ->where('id', $assignment_id)
            ->exists();

        $this->assertTrue($assignment_exists);
    }

    protected function assert_assignment_marked_for_expansion(int $assignment_id) {
        $assignment_exists = track_assignment::repository()
            ->where('expand', true)
            ->where('id', $assignment_id)
            ->exists();

        $this->assertTrue($assignment_exists);
    }

    private function prepare_assignments() {
        $test_data = new class() {
            public $user1;
            public $user2;
            public $cohort1;
            public $cohort2;
            public $activity1;
            public $track1;
            public $assignment1;
            public $assignment2;
        };

        $test_data->user1 = $this->generator()->create_user();
        $test_data->user2 = $this->generator()->create_user();
        $test_data->cohort1 = $this->generator()->create_cohort();
        $test_data->cohort2 = $this->generator()->create_cohort();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $this->setAdminUser();

        $test_data->activity1 = $perform_generator->create_activity_in_container();

        $test_data->track1 = $perform_generator
            ->create_activity_tracks($test_data->activity1)
            ->first();

        $test_data->assignment1 = new track_assignment([
            'track_id' => $test_data->track1->id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::COHORT,
            'user_group_id' => $test_data->cohort1->id,
            'created_by' => 0,
            'expand' => false
        ]);
        $test_data->assignment1->save();

        $test_data->assignment2 = new track_assignment([
            'track_id' => $test_data->track1->id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::COHORT,
            'user_group_id' => $test_data->cohort2->id,
            'created_by' => 0,
            'expand' => false
        ]);
        $test_data->assignment2->save();

        return $test_data;
    }

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator() {
        return self::getDataGenerator();
    }

    private function get_expandable_track_assignments() {
        return track_assignment::repository()
            ->where('expand', true)
            ->get();
    }

}
