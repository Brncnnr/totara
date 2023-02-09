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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core_phpunit\testcase;
use core\webapi\execution_context;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\models\assignment as assignment_model;
use totara_competency\user_groups;
use totara_competency\webapi\resolver\mutation\archive_user_assignment;
use core\orm\query\builder;
use totara_job\job_assignment;
use totara_competency\models\assignment_actions;
use totara_competency\expand_task;
use totara_competency\helpers\capability_helper;
use totara_hierarchy\entity\competency;

/**
 * Class webapi_resolver_mutation_archive_user_assignment_test
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_mutation_archive_user_assignment_test extends testcase {

    /**
     * @var array Generated data.
     */
    private $data;

    /**
     * @var \totara_hierarchy\testing\generator Hierarchy generator
     */
    private $generator;

    /**
     * Tests a manager can archive self and other assigned competency assignments.
     *
     * @return void
     */
    public function test_manager_archives_self_and_other_assigned_assignment(): void {
        $this->setUser($this->data['manager']->id);
        $assignment_ids = [];
        $assignment_ids['self'] = $this->data['self_assignment']->first()->id;
        $assignment_ids['other'] = $this->data['other_assignment']->first()->id;

        foreach ($assignment_ids as $assignment_id) {
            $assignment = archive_user_assignment::resolve(
                [
                    'assignment_id' => $assignment_id,
                ],
                execution_context::create('dev')
            );
            $this->assertInstanceOf(assignment_model::class, $assignment);
            $this->assertTrue($assignment->is_archived());
        }
    }

    /**
     * Tests a user can archive only self assigned competency assignments
     * and fail on archiving other assigned competency assignment.
     *
     * @return void
     *
     * @throws moodle_exception
     */
    public function test_user_archives_self_and_other_assigned_assignment(): void {
        $this->setUser($this->data['user']->id);
        $this->user_archives_self_assignment();
        $this->user_cannot_archive_other_assignment();
    }

    /**
     * Runs scenario test if user can archive self assignment.
     *
     * @return void
     *
     * @throws moodle_exception
     */
    private function user_archives_self_assignment(): void {
        $self_assignment_id = $this->data['self_assignment']->first()->id;
        $assignment = archive_user_assignment::resolve(
            [
                'assignment_id' => $self_assignment_id,
            ],
            execution_context::create('dev')
        );
        $this->assertInstanceOf(assignment_model::class ,$assignment);
        $this->assertTrue($assignment->is_archived());
    }

    /**
     * Runs scenario test user cannot archive other assignment.
     *
     * @return void
     *
     * @throws moodle_exception
     */
    private function user_cannot_archive_other_assignment(): void {
        $other_assignment_id = $this->data['other_assignment']->first()->id;
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('You do not have the capability to archive this assignment.');
        archive_user_assignment::resolve(
            [
                'assignment_id' => $other_assignment_id,
            ],
            execution_context::create('dev')
        );
    }

    /**
     * Test can unassign capability check.
     *
     * @return void
     */
    public function test_can_archive_capability_check(): void {
        $this->setUser($this->data['user']->id);

        $user_can_archive_self_assignment = capability_helper::can_user_archive_assignment(
            $this->data['user']->id,
            $this->data['self_assignment']->first()->id
        );
        $this->assertTrue($user_can_archive_self_assignment);

        $user_cant_archive_other_assignment = capability_helper::can_user_archive_assignment(
            $this->data['user']->id,
            $this->data['other_assignment']->first()->id
        );
        $this->assertNotTrue($user_cant_archive_other_assignment);

        $this->setUser($this->data['manager']->id);

        $manager_can_archive_self_assignment = capability_helper::can_user_archive_assignment(
            $this->data['manager']->id,
            $this->data['self_assignment']->first()->id
        );
        $this->assertTrue($manager_can_archive_self_assignment);

        $manager_can_archive_other_assignment = capability_helper::can_user_archive_assignment(
            $this->data['manager']->id,
            $this->data['other_assignment']->first()->id
        );
        $this->assertTrue($manager_can_archive_other_assignment);
    }

    /**
     * Setup for tests.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->set_generator();
        $this->create_framework();
        $this->create_competency_and_set_assignable();
        $this->create_users_and_grant_permissions();
        $this->create_job_assignments();
        $this->create_competency_assignment();
        (new expand_task($GLOBALS['DB']))->expand_all();
    }

    /**
     * Setup competency generator.
     *
     * @return void
     */
    private function set_generator(): void {
        $this->generator = \totara_hierarchy\testing\generator::instance();
    }

    /**
     * Create competency framework.
     *
     * @return void
     */
    private function create_framework(): void {
        $this->data['framework'] = $this->generator->create_comp_frame([]);
    }

    /**
     * Create and setup test competency.
     *
     * @return void
     */
    private function create_competency_and_set_assignable(): void {
        $this->data['competency'] = $this->generator->create_comp(
            [
                'frameworkid' => $this->data['framework']->id,
            ]
        );
        $this->set_self_and_other_competency_assignable($this->data['competency']->id);
    }

    /**
     * Set competency to allow self and other assignable.
     *
     * @param int $comp_id competency id.
     *
     * @return void
     */
    private function set_self_and_other_competency_assignable(int $comp_id): void {
        $assignable_types = [competency::ASSIGNMENT_CREATE_SELF, competency::ASSIGNMENT_CREATE_OTHER];

        foreach ($assignable_types as $assignable_type) {
            builder::table('comp_assign_availability')
                ->insert([
                    'comp_id' => $comp_id,
                    'availability' => $assignable_type
                ]);
        }
    }

    /**
     * Create and grant user permissions.
     *
     * @return void
     */
    private function create_users_and_grant_permissions(): void {
        $this->data['manager'] = $this->getDataGenerator()->create_user();
        $this->data['user'] = $this->getDataGenerator()->create_user();
        $this->grant_permissions();
    }

    /**
     * Grant permissions.
     *
     * @return void
     */
    private function grant_permissions(): void {
        $this->grant_users_assign_permissions();
    }

    /**
     * Grant users permissions to assign competencies.
     *
     * @return void
     */
    private function grant_users_assign_permissions(): void {
        global $DB;

        $context_user = context_user::instance($this->data['user']->id);
        $user_role_id = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('totara/competency:assign_other', CAP_ALLOW, $user_role_id, $context_user);
    }

    /**
     * Create job assignments.
     *
     * @return void
     */
    private function create_job_assignments(): void {
        $manager_job = job_assignment::create(
            [
                'userid' => $this->data['manager']->id,
                'idnumber' => 'manager',
            ]
        );
        job_assignment::create(
            [
                'userid' => $this->data['user']->id,
                'idnumber' => 'user_manager',
                'managerjaid' => $manager_job->id,
            ]
        );
    }

    /**
     * Create competency assignments.
     *
     * @return void
     */
    private function create_competency_assignment(): void {
        $user_groups = [user_groups::USER => [$this->data['user']->id]];
        $assignments_action_model = new assignment_actions();
        $competency_id = (int)$this->data['competency']->id;
        $this->setUser($this->data['user']->id);
        $this->data['self_assignment'] = $assignments_action_model->create_from_competencies(
            [$competency_id],
            $user_groups,
            assignment_entity::TYPE_SELF,
            assignment_entity::STATUS_ACTIVE
        );
        $this->setUser($this->data['manager']->id);
        $this->data['other_assignment'] = $assignments_action_model->create_from_competencies(
            [$competency_id],
            $user_groups,
            assignment_entity::TYPE_OTHER,
            assignment_entity::STATUS_ACTIVE
        );
    }

    /**
     * Tears down stored data.
     *
     * @return void
     */
    protected function tearDown(): void {
        $this->data = null;
        $this->generator = null;
    }
}
