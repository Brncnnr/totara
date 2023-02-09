<?php
/**
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

use totara_competency\admin_setting_continuous_tracking;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\models\assignment_user;
use totara_competency\user_groups;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/assignment_actions_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_assignment_user_system_testcase extends totara_competency_assignment_actions_testcase {

    public function test_create_system_assignment_without_any_previous_assignments() {
        ['competencies' => $competencies] = $this->generate_assignments();

        $user = $this->getDataGenerator()->create_user();

        $assignment_user = new assignment_user($user->id);
        $assignment_user->create_system_assignment($competencies[0]->id);

        $new_assignment = assignment::repository()
            ->where('type', assignment::TYPE_SYSTEM)
            ->where('user_group_type', user_groups::USER)
            ->where('user_group_id', $user->id)
            ->order_by('type')
            ->first();
        $this->assertNotEmpty($new_assignment);
    }

    public function test_create_system_assignment_with_archived_assignment() {
        ['competencies' => $competencies] = $this->generate_assignments();

        $user = $this->getDataGenerator()->create_user();

        /** @var assignment $assignment */
        ['assignment' => $assignment] = $this->create_assignment_for_user($competencies, $user);

        $assignment->status = assignment::STATUS_ARCHIVED;
        $assignment->save();

        $this->expand();

        $assignment_user = new assignment_user($user->id);
        $assignment_user->create_system_assignment($competencies[0]->id);

        $new_assignment = assignment::repository()
            ->where('type', assignment::TYPE_SYSTEM)
            ->where('user_group_type', user_groups::USER)
            ->where('user_group_id', $user->id)
            ->order_by('type')
            ->first();
        $this->assertNotEmpty($new_assignment);
    }

    public function test_create_system_assignment_with_draft_assignment() {
        ['competencies' => $competencies] = $this->generate_assignments();

        $user = $this->getDataGenerator()->create_user();

        /** @var assignment $assignment */
        ['assignment' => $assignment] = $this->create_assignment_for_user($competencies, $user);

        $assignment->status = assignment::STATUS_DRAFT;
        $assignment->save();

        $this->expand();

        $assignment_user = new assignment_user($user->id);
        $assignment_user->create_system_assignment($competencies[0]->id);

        $new_assignment = assignment::repository()
            ->where('type', assignment::TYPE_SYSTEM)
            ->where('user_group_type', user_groups::USER)
            ->where('user_group_id', $user->id)
            ->order_by('type')
            ->first();
        $this->assertNotEmpty($new_assignment);
    }

    public function test_create_system_assignment_with_active_assignment() {
        ['competencies' => $competencies] = $this->generate_assignments();

        $user = $this->getDataGenerator()->create_user();

        /** @var assignment $assignment */
        $this->create_assignment_for_user($competencies, $user);

        $assignment_user = new assignment_user($user->id);
        $assignment_user->create_system_assignment($competencies[0]->id);

        $new_assignment = assignment::repository()
            ->where('type', assignment::TYPE_SYSTEM)
            ->where('user_group_type', user_groups::USER)
            ->where('user_group_id', $user->id)
            ->order_by('type')
            ->first();
        $this->assertEmpty($new_assignment);
    }

    public function test_create_system_assignment_with_deleted_users() {
        ['competencies' => $competencies] = $this->generate_assignments();

        $user = $this->getDataGenerator()->create_user();

        /** @var assignment $assignment */
        ['assignment' => $assignment] = $this->create_assignment_for_user($competencies, $user);
        // Make it a draft to ensure there are no active assignments
        $assignment->status = assignment::STATUS_DRAFT;
        $assignment->save();

        $this->expand();

        delete_user($user);

        $assignment_user = new assignment_user($user->id);
        $assignment_user->create_system_assignment($competencies[0]->id);

        $new_assignment = assignment::repository()
            ->where('type', assignment::TYPE_SYSTEM)
            ->where('user_group_type', user_groups::USER)
            ->where('user_group_id', $user->id)
            ->order_by('type')
            ->first();
        $this->assertEmpty($new_assignment);
    }

    public function test_create_system_assignment_on_unassign() {
        set_config('continuous_tracking', admin_setting_continuous_tracking::ENABLED, 'totara_competency');

        ['competencies' => $competencies] = $this->generate_assignments();

        $user = $this->getDataGenerator()->create_user();

        ['position' => $pos, 'assignment' => $assignment] = $this->create_assignment_for_user($competencies, $user);

        // Unassign the user from position
        $job_assignment = job_assignment::get_first($user->id);
        job_assignment::delete($job_assignment);

        $this->expand();

        // The user got unassigned from the original assignment
        $this->assertEquals(0, competency_assignment_user::repository()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->count()
        );

        // The original assignment is still there
        $this->assertEquals(1, assignment::repository()
            ->where('type', assignment::TYPE_ADMIN)
            ->where('user_group_type', user_groups::POSITION)
            ->where('user_group_id', $pos->id)
            ->count()
        );

        // There must be a system assignment for this user now
        /** @var assignment $new_assignment */
        $new_assignment = assignment::repository()
            ->where('type', assignment::TYPE_SYSTEM)
            ->where('user_group_type', user_groups::USER)
            ->where('user_group_id', $user->id)
            ->order_by('type')
            ->first();
        $this->assertNotEmpty($new_assignment);

        // Now the individual user record is there
        $this->assertEquals(1, competency_assignment_user::repository()
            ->where('assignment_id', $new_assignment->id)
            ->where('user_id', $user->id)
            ->count()
        );
    }

    public function test_dont_create_system_assignment_for_deleted_users() {
        ['competencies' => $competencies] = $this->generate_assignments();

        $user = $this->getDataGenerator()->create_user();

        ['position' => $pos, 'assignment' => $assignment] = $this->create_assignment_for_user($competencies, $user);

        $this->expand();

        $this->assertEquals(1, competency_assignment_user::repository()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->count()
        );

        // Deleting the user should delete all assignment records right away
        // without the need to trigger the expand task
        delete_user($user);

        // The user got unassigned from the original assignment
        $this->assertEquals(0, competency_assignment_user::repository()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->count()
        );

        // The original assignment is still there
        $this->assertEquals(1, assignment::repository()
            ->where('type', assignment::TYPE_ADMIN)
            ->where('user_group_type', user_groups::POSITION)
            ->where('user_group_id', $pos->id)
            ->count()
        );
    }

    private function expand() {
        // We need the expanded users for the logging to work
        $expand_task = new \totara_competency\expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }

    private function create_assignment_for_user(array $competencies, $user) {
        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_pos_frame([]);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id]);

        $job_data = [
            'userid' => $user->id,
            'idnumber' => random_string(5),
            'fullname' => 'Developer',
            'positionid' => $pos->id
        ];
        job_assignment::create($job_data);

        $record = $this->generator()->assignment_generator()->create_position_assignment(
            $competencies[0]->id,
            $pos->id,
            ['status' => assignment::STATUS_ACTIVE]
        );
        $assignment = new assignment($record);

        $this->expand();

        return ['position' => $pos, 'assignment' => $assignment];
    }

}