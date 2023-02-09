<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 * @category test
 */

use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\expand_task;
use totara_competency\models\assignment_actions;
use totara_competency\models\assignment_user_log;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/assignment_actions_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_user_log_testcase extends totara_competency_assignment_actions_testcase {

    public function test_action_name() {
        $log = new competency_assignment_user_log();
        $log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $this->assertEquals(competency_assignment_user_log::ACTION_ASSIGNED_NAME, $log->action_name);

        $result = $log->to_array();
        $this->assertArrayHasKey('action_name', $result);
        $this->assertEquals(competency_assignment_user_log::ACTION_ASSIGNED_NAME, $result['action_name']);

        $log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $this->assertEquals(competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP_NAME, $log->action_name);

        $log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $this->assertEquals(competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED_NAME, $log->action_name);

        $log = new competency_assignment_user_log();
        $log->action = competency_assignment_user_log::ACTION_TRACKING_START;
        $this->assertEquals(competency_assignment_user_log::ACTION_TRACKING_START_NAME, $log->action_name);

        $log = new competency_assignment_user_log();
        $log->action = competency_assignment_user_log::ACTION_TRACKING_END;
        $this->assertEquals(competency_assignment_user_log::ACTION_TRACKING_END_NAME, $log->action_name);

        $log->action = 'unknown_status';
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown action name for assignment user log action \'unknown_status\'');
        $test = $log->action_name;
    }

    public function test_log() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment_id = $assignments[0]->id;
        $user_id = $assignments[0]->user_group_id;

        $log = new assignment_user_log($assignment_id);

        $log->log_assign($user_id);
        $this->assert_log_entry_exists($user_id, $assignment_id, competency_assignment_user_log::ACTION_ASSIGNED);

        $log->log_unassign_user_group($user_id);
        $this->assert_log_entry_exists($user_id, $assignment_id, competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP);

        $log->log_archive($user_id);
        $this->assert_log_entry_exists($user_id, $assignment_id, competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED);
    }

    public function test_log_actions() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $model = new assignment_actions();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $model->activate($assignment1->id);
        $this->assert_has_log_entry_amount(0, $assignment1->id);

        $this->expand();

        $this->assert_has_log_entry_amount(2, $assignment1->id);
        $this->assert_log_entry_exists($assignment1->user_group_id, $assignment1->id, competency_assignment_user_log::ACTION_ASSIGNED);
        $this->assert_log_entry_exists($assignment1->user_group_id, $assignment1->id, competency_assignment_user_log::ACTION_TRACKING_START);

        $this->assert_has_log_entry_amount(2, $assignment2->id);
        $this->assert_log_entry_exists($assignment2->user_group_id, $assignment2->id, competency_assignment_user_log::ACTION_ASSIGNED);
        $this->assert_log_entry_exists($assignment2->user_group_id, $assignment2->id, competency_assignment_user_log::ACTION_TRACKING_START);

        // ARCHIVE
        $model->archive($assignment1->id);

        $this->assert_has_log_entry_amount(4, $assignment1->id);
        $this->assert_log_entry_exists($assignment1->user_group_id, $assignment1->id, competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED);
        $this->assert_log_entry_exists($assignment1->user_group_id, $assignment1->id, competency_assignment_user_log::ACTION_TRACKING_END);

        $model->delete($assignment1->id);

        // Records and logs for assignment should be gone after delete
        $this->assertEquals(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment1->id)
                ->count()
        );
        $this->assert_has_log_entry_amount(0, $assignment1->id);

        // Control record should still be there
        $this->assertEquals(
            1,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment2->id)
                ->count()
        );
        $this->assert_has_log_entry_amount(2, $assignment2->id);
    }

    public function test_log_user_added_to_user_group() {
        \totara_competency\settings::enable_continuous_tracking();
        \totara_competency\settings::unassign_keep_records();

        ['competencies' => $competencies] = $this->generate_assignments();

        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $user = $this->getDataGenerator()->create_user();

        $assignment = $this->create_position_assignment($user->id, $pos->id, $competencies[0]->id);
        $this->assert_has_log_entry_amount(0, $assignment->id);

        $this->expand();

        // User table should now be filled
        $this->assertEquals(
            1,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment->id)
                ->count()
        );

        $this->assert_has_log_entry_amount(2, $assignment->id);
        $this->assert_log_entry_exists($user->id, $assignment->id, competency_assignment_user_log::ACTION_ASSIGNED);
        $this->assert_log_entry_exists($user->id, $assignment->id, competency_assignment_user_log::ACTION_TRACKING_START);

        $this->delete_job_assignment($user->id);

        $this->expand();

        // Check that User got unassigned, means the record in the user table should be gone
        $this->assertEquals(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment->id)
                ->count()
        );

        $this->assert_has_log_entry_amount(3, $assignment->id);
        $this->assert_log_entry_exists($user->id, $assignment->id, competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP);

        // User still has an active assignment (system assignment due to continuous tracking)
        // Let's archive the existing system assignment to really end the tracking
        $new_assignment = assignment::repository()
            ->join(['totara_competency_assignment_users', 'ass_user'], 'id', 'assignment_id')
            ->where('ass_user.user_id', $user->id)
            ->where('type', assignment::TYPE_SYSTEM)
            ->order_by('id')
            ->first();
        $this->assertNotEmpty($new_assignment);

        $this->assert_has_log_entry_amount(1, $new_assignment->id);
        $this->assert_log_entry_exists($user->id, $new_assignment->id, competency_assignment_user_log::ACTION_ASSIGNED);

        (new assignment_actions())->archive($new_assignment->id);

        $this->assert_has_log_entry_amount(3, $new_assignment->id);
        $this->assert_log_entry_exists($user->id, $new_assignment->id, competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED);
        $this->assert_log_entry_exists($user->id, $new_assignment->id, competency_assignment_user_log::ACTION_TRACKING_END);
    }

    public function test_log_draft() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        // Expand and check that there are the expected records in the assignment user table
        $this->expand();
        $this->assertEquals(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment1->id)
                ->count()
        );
        $this->assertEquals(
            0,
            competency_assignment_user_log::repository()
                ->where('assignment_id', $assignment1->id)
                ->count()
        );

        $model = new assignment_actions();
        $model->delete($assignment1->id);

        $this->assertEquals(
            0,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment1->id)
                ->count()
        );
        $this->assertEquals(
            0,
            competency_assignment_user_log::repository()
                ->where('assignment_id', $assignment1->id)
                ->count()
        );
    }

    private function create_position_assignment(int $user_id, int $pos_id, int $competency_id): assignment {
        $job_data = [
            'userid' => $user_id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $pos_id
        ];
        job_assignment::create($job_data);

        $record = $this->generator()->assignment_generator()->create_position_assignment(
            $competency_id,
            $pos_id,
            ['status' => assignment::STATUS_ACTIVE]
        );
        return new assignment($record);
    }

    private function delete_job_assignment(int $user_id) {
        $job_assignment = job_assignment::get_first($user_id);
        job_assignment::delete($job_assignment);
    }

    private function assert_has_log_entry_amount(int $expected, int $assignment_id) {
        $this->assertEquals(
            $expected,
            competency_assignment_user_log::repository()
                ->where('assignment_id', $assignment_id)
                ->count(),
            "Log does not contain the expected amount of log entries for assignment $assignment_id"
        );
    }

    private function assert_log_entry_exists(int $user_id, int $assignment_id, int $action) {
        $this->assertTrue(
            competency_assignment_user_log::repository()
                ->where('user_id', $user_id)
                ->where('assignment_id', $assignment_id)
                ->where('action', $action)
                ->where('created_at', '>', 0)
                ->exists(),
            "Log entry with action $action for assignment $assignment_id and user $user_id does not exist"
        );
    }

    private function expand() {
        // We need the expanded users for the logging to work
        $expand_task = new expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }

}
