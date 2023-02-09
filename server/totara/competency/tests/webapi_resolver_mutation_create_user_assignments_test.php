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
 * @subpackage test
 */

use core\orm\collection;
use core\orm\query\builder;
use core_phpunit\testcase;
use core\webapi\execution_context;
use totara_competency\assignment_create_exception;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\entity\competency_assignment_user;
use totara_competency\models\assignment as assignment_model;
use totara_competency\user_groups;
use totara_competency\webapi\resolver\mutation\create_user_assignments;
use totara_hierarchy\entity\competency as competency_entity;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();


/**
 * Tests the mutation to create assignments for self or other
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_mutation_create_user_assignments_testcase extends testcase {

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return execution_context::create($type, $operation);
    }

    public function test_user_cannot_self_assign_without_permission() {
        global $DB;

        $data = $this->create_data();

        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        unassign_capability('totara/competency:assign_self', $user_role_id);

        $this->setUser($data->user1->id);

        $args = [
            'user_id' => $data->user1->id,
            'competency_ids' => [$data->comp1->id, $data->comp2->id],
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Assign competency to yourself');

        create_user_assignments::resolve($args, $this->get_execution_context());
    }

    public function test_user_by_default_can_not_assign_to_other_user() {
        $data = $this->create_data();

        $this->setUser($data->user1);

        $args = [
            'user_id' => $data->user2->id,
            'competency_ids' => [$data->comp1->id, $data->comp2->id],
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Assign competency to other users');

        create_user_assignments::resolve($args, $this->get_execution_context());
    }

    public function test_user_cannot_assign_to_self_without_assignable_flag() {
        $data = $this->create_data();

        $this->setUser($data->user1);

        $expected_comp_ids = [$data->comp1->id, $data->comp2->id];

        $args = [
            'user_id' => $data->user1->id,
            'competency_ids' => $expected_comp_ids,
        ];

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Competency cannot be be assigned by given type');
        create_user_assignments::resolve($args, $this->get_execution_context());
    }

    public function test_user_can_assign_to_self() {
        $data = $this->create_data();

        $this->setUser($data->user1);

        $expected_comp_ids = [$data->comp1->id, $data->comp2->id];

        $this->set_self_assignable($data->comp1->id);
        $this->set_self_assignable($data->comp2->id);

        $args = [
            'user_id' => $data->user1->id,
            'competency_ids' => $expected_comp_ids,
        ];

        $result = create_user_assignments::resolve($args, $this->get_execution_context());
        $this->assertInstanceOf(collection::class, $result);
        $this->assertCount(2, $result);
        $comp_ids = [];
        foreach ($result as $assignment) {
            $comp_ids[] = $assignment->get_field('competency_id');
            $this->assertInstanceOf(assignment_model::class, $assignment);
            $this->assertEquals(user_groups::USER, $assignment->get_field('user_group_type'));
            $this->assertEquals($data->user1->id, $assignment->get_field('user_group_id'));
            $this->assertEquals($data->user1->id, $assignment->get_field('created_by'));
            $this->assertEquals(assignment_entity::TYPE_SELF, $assignment->get_field('type'));
            $this->assertGreaterThan(0, $assignment->get_field('created_at'));
        }
        $this->assertEqualsCanonicalizing($expected_comp_ids, $comp_ids);

        // Check that assigments are already expanded
        $user_assignments = competency_assignment_user::repository()
            ->where('user_id', $data->user1->id)
            ->get();

        $this->assertCount(2, $user_assignments);
    }

    public function test_manager_cannot_assign_to_other_user_without_assignable_flag() {
        $data = $this->create_data();

        // User is now managing another user and can assign competencies for them
        $manager_job = job_assignment::create(['userid' => $data->user1->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $data->user2->id, 'idnumber' => 2, 'managerjaid' => $manager_job->id]);

        $this->setUser($data->user1);

        $expected_comp_ids = [$data->comp1->id, $data->comp2->id];

        $args = [
            'user_id' => $data->user2->id,
            'competency_ids' => $expected_comp_ids,
        ];

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Competency cannot be be assigned by given type');
        create_user_assignments::resolve($args, $this->get_execution_context());
    }

    public function test_manager_can_assign_to_team_member() {
        $data = $this->create_data();

        // User is now managing another user and can assign competencies for them
        $manager_job = job_assignment::create(['userid' => $data->user1->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $data->user2->id, 'idnumber' => 2, 'managerjaid' => $manager_job->id]);

        $this->setUser($data->user1);

        $expected_comp_ids = [$data->comp1->id, $data->comp2->id];

        $this->set_other_assignable($data->comp1->id);
        $this->set_other_assignable($data->comp2->id);

        $args = [
            'user_id' => $data->user2->id,
            'competency_ids' => $expected_comp_ids,
        ];

        $result = create_user_assignments::resolve($args, $this->get_execution_context());
        $this->assertInstanceOf(collection::class, $result);
        $this->assertCount(2, $result);
        $comp_ids = [];
        foreach ($result as $assignment) {
            $comp_ids[] = $assignment->get_field('competency_id');
            $this->assertInstanceOf(assignment_model::class, $assignment);
            $this->assertEquals(user_groups::USER, $assignment->get_field('user_group_type'));
            $this->assertEquals($data->user2->id, $assignment->get_field('user_group_id'));
            $this->assertEquals($data->user1->id, $assignment->get_field('created_by'));
            $this->assertEquals(assignment_entity::TYPE_OTHER, $assignment->get_field('type'));
            $this->assertGreaterThan(0, $assignment->get_field('created_at'));
        }
        $this->assertEqualsCanonicalizing($expected_comp_ids, $comp_ids);

        // Check that assigments are already expanded
        $user_assignments = competency_assignment_user::repository()
            ->where('user_id', $data->user2->id)
            ->get();

        $this->assertCount(2, $user_assignments);
    }

    public function test_user_can_assign_to_others_with_permission() {
        global $DB;

        $data = $this->create_data();

        $user2_context = context_user::instance($data->user2->id);
        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        assign_capability('totara/competency:assign_other', CAP_ALLOW, $user_role_id, $user2_context->id);

        $this->setUser($data->user1);

        $expected_comp_ids = [$data->comp1->id, $data->comp2->id];

        $this->set_other_assignable($data->comp1->id);
        $this->set_other_assignable($data->comp2->id);

        $args = [
            'user_id' => $data->user2->id,
            'competency_ids' => $expected_comp_ids,
        ];

        $result = create_user_assignments::resolve($args, $this->get_execution_context());
        $this->assertInstanceOf(collection::class, $result);
        $this->assertCount(2, $result);
    }

    protected function create_data() {
        $generator = $this->getDataGenerator();

        $data = new class() {
            public $fw1;
            public $user1;
            public $user2;
            public $comp1;
            public $comp2;
        };
        $data->fw1 = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $data->comp1 = $this->generator()->create_competency(null, $data->fw1->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
        ]);

        $data->comp2 = $this->generator()->create_competency(null, $data->fw1->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
        ]);

        $data->user1 = $generator->create_user();
        $data->user2 = $generator->create_user();

        return $data;
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }

    private function set_self_assignable($competency_id) {
        builder::table('comp_assign_availability')
            ->insert([
                'comp_id' => $competency_id,
                'availability' => competency_entity::ASSIGNMENT_CREATE_SELF
            ]);
    }

    private function set_other_assignable($competency_id) {
        builder::table('comp_assign_availability')
            ->insert([
                'comp_id' => $competency_id,
                'availability' => competency_entity::ASSIGNMENT_CREATE_OTHER
            ]);
    }

}
