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

use core\orm\query\builder;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\entity\competency_assignment_user_repository;
use totara_competency\expand_task;
use totara_competency\models\assignment_user;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_assignment_user_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_remove_orphaned_records() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_ACTIVE;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ACTIVE;
        $assignment3->save();

        $task = new expand_task($GLOBALS['DB']);
        $task->expand_all();
        $this->assertEquals(3, competency_assignment_user::repository()->count());

        $assignment1->status = assignment::STATUS_ARCHIVED;
        $assignment1->save();

        $assignment2->status = assignment::STATUS_DRAFT;
        $assignment2->save();

        competency_assignment_user_repository::remove_orphaned_records();

        $this->assertEquals(
            1,
            competency_assignment_user::repository()->count()
        );
        $this->assertEquals(
            1,
            competency_assignment_user::repository()
                ->where('assignment_id', $assignment3->id)
                ->count()
        );
    }

    public function test_has_assignment() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);

        $user_id = $assignment1->user_group_id;

        $assignment_user = new assignment_user($user_id);

        $this->expand();

        $this->assertTrue($assignment_user->has_assignment($assignment1->id));
        $this->assertFalse($assignment_user->has_assignment(999));

        $gen = $this->generator();
        $cohort = $gen->assignment_generator()->create_cohort();
        $assignment2 = $gen->assignment_generator()->create_cohort_assignment($assignment1->competency_id, $cohort->id);
        cohort_add_member($cohort->id, $user_id);

        $this->assertFalse($assignment_user->has_assignment($assignment2->id));

        $this->expand();

        $this->assertTrue($assignment_user->has_assignment($assignment1->id));
        $this->assertTrue($assignment_user->has_assignment($assignment2->id));
    }

    public function test_active_assignments() {
        ['assignments' => $assignments] = $this->generate_assignments();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_ACTIVE;
        $assignment1->save();

        $user_id = $assignment1->user_group_id;

        $assignment_user = new assignment_user($user_id);
        $this->assertFalse($assignment_user->has_active_assignments($assignment1->competency_id));

        $this->expand();

        $this->assertTrue($assignment_user->has_active_assignments($assignment1->competency_id));

        $gen = $this->generator();
        $cohort = $gen->assignment_generator()->create_cohort();
        $assignment2 = $gen->assignment_generator()->create_cohort_assignment($assignment1->competency_id, $cohort->id, ['status' => assignment::STATUS_ACTIVE]);
        cohort_add_member($cohort->id, $user_id);

        $expand_task = new expand_task($GLOBALS['DB']);
        $expand_task->expand_all();

        $this->assertTrue($assignment_user->has_active_assignments($assignment1->competency_id));
    }

    public function test_user_has_archived_assignments() {

        $user = $this->getDataGenerator()->create_user();

        $competency = $this->generator()->create_competency();
        $assignment = $this->generator()
            ->assignment_generator()
            ->create_user_assignment($competency->id, $user->id, ['status' => assignment::STATUS_ARCHIVED]);

        $this->expand();

        $assignment_user = new assignment_user($user->id);

        $this->assertFalse($assignment_user->has_active_assignments($assignment->competency_id));
        // This will be false yet, until we create a fake log entry
        $this->assertFalse($assignment_user->has_archived_assignments($assignment->competency_id));

        // Let's create a fake assignment and see that it works.
        // Fake log entry
        builder::table('totara_competency_assignment_user_logs')
            ->insert([
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'action' => competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED,
                'created_at' => time(),
            ]);

        $this->assertFalse($assignment_user->has_active_assignments($assignment->competency_id));
        $this->assertTrue($assignment_user->has_archived_assignments($assignment->competency_id));
    }

    /**
     * Create a few competencies with knows names to test search
     */
    protected function generate_assignments() {
        $data = [
            'competencies' => [],
            'frameworks' => [],
            'assignments' => [],
            'types' => [],
        ];

        $data['frameworks'][] = $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);
        $data['frameworks'][] = $fw2 = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $data['types'][] = $type1 = $this->generator()->hierarchy_generator()->create_comp_type(['idnumber' => 'type1']);
        $data['types'][] = $type2 = $this->generator()->hierarchy_generator()->create_comp_type(['idnumber' => 'type2']);

        $data['competencies'][] = $one = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type1,
        ]);

        $data['competencies'][] = $two = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type1,
        ]);

        $data['competencies'][] = $three = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $one->id,
            'typeid' => $type2,
        ]);

        // Create assignments for competencies
        $gen = $this->generator()->assignment_generator();
        $data['assignments'][] = $gen->create_user_assignment($one->id);
        $data['assignments'][] = $gen->create_user_assignment($two->id);
        $data['assignments'][] = $gen->create_user_assignment($three->id);

        return $data;
    }

    private function expand() {
        $expand_task = new expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }
}