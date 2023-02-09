<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use core\orm\query\builder;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\assignment\approver_type\user as approver_type_user;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\assignment\helper\csv_upload;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * Class mod_approval_csv_assignment_overrides_upload_testcase
 */
class mod_approval_csv_assignment_overrides_upload_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * @var workflow_entity
     */
    private $workflow;

    /**
     * @var assignment_entity
     */
    private $assignment;

    /**
     * @var workflow_stage_entity
     */
    private $stage1;

    /**
     * @var workflow_stage_approval_level_entity
     */
    private $level1;

    /**
     * @var workflow_stage_approval_level_entity
     */
    private $level2;

    /**
     * @var workflow_stage_entity
     */
    private $stage2;

    protected function setUp(): void {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment('Testing', false, false);
        $workflow = workflow::load_by_entity($workflow);
        $this->workflow = $workflow;
        /** @var \mod_approval\entity\assignment\assignment $this->assignment */
        $this->assignment = $assignment;

        $assignment_context = (assignment::load_by_entity($this->assignment))->get_context();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        $manager = \core\testing\generator::instance()->create_user(['username' => 'manager']);
        role_assign($workflow_manager_role->id, $manager->id, $assignment_context, 'mod_approval', $assignment->id);

        // Add a second approval level
        $workflow_version = $workflow->latest_version;
        $stage1 = $workflow_version->get_stages()->first();
        $stage2 = $workflow_version->get_next_stage($stage1->id);

        $this->level2 = $stage2->add_approval_level('Level 2');
    }

    protected function tearDown(): void {
        $this->workflow = null;
        $this->assignment = null;
        $this->stage1 = null;
        $this->level1 = null;
        $this->level2 = null;
        $this->stage2 = null;
    }

    public function test_upload_csv_content() {
        $file = __DIR__ . '/fixtures/assignment/assignment_overrides.csv';
        $entry = (object)[
            'content' => file_get_contents($file),
            'delimiter' => 'comma',
            'encoding' => 'utf-8'
        ];

        $workflow_id = $this->workflow->id;
        $csv = csv_upload::instance($workflow_id);
        $csv->upload_csv_content($entry);

        $data = $csv->get_all_user_data();

        $this->assertNotEmpty($csv->get_process_id());
        $this->assertEquals($workflow_id, $csv->get_action_id());
        $this->assertNotEmpty($csv->get_all_user_data());
        $this->assertCount(4, $data['raw_data']);
    }

    private function get_workflowmanager() {
        return builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
    }

    private function upload_and_process_assignments_csv() {
        $manager_users = [];
        foreach (['a', 'b', 'c', 'd'] as $index) {
            $manager_index = 'manager_' . $index;
            ${$manager_index} = \core\testing\generator::instance()->create_user(['username' => $manager_index]);
            $manager_users[$index] = ${$manager_index};
        }

        $approver_users = [];
        $new_approvers = [];
        foreach (['c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'] as $index) {
            $approver_index = 'approver_' . $index;
            ${$approver_index} = \core\testing\generator::instance()->create_user(['username' => $approver_index]);
            $new_approvers[${$approver_index}->id] = ${$approver_index};
            $approver_users[$index] = ${$approver_index};
        }

        $assignment_context = (assignment::load_by_entity($this->assignment))->get_context();

        // Before upload CSV
        $assignments = assignment_entity::repository()
            ->select('*')
            ->where('course', '=', $this->workflow->course_id)
            ->get();
        $this->assertEquals(1, $assignments->count());

        $workflow_manager_role = $this->get_workflowmanager();
        $role_users = get_role_users($workflow_manager_role->id, $assignment_context, true, 'u.id', 'u.id ASC');
        $this->assertCount(1, $role_users);

        // Upload 1
        $workflow = workflow::load_by_id($this->workflow->id);
        $file =  realpath(__DIR__ . '/fixtures/assignment') . '/assignment_overrides.csv';
        $entry = (object)[
            'content' => file_get_contents($file),
            'delimiter' => 'comma',
            'encoding' => 'utf-8'
        ];
        $csv = csv_upload::instance($workflow->id);
        $csv->upload_csv_content($entry);
        $data = $csv->get_all_user_data();
        [$successful, $errors] = $csv->process_data();
        $this->assertEquals(6, $data['count']['total']);
        $this->assertEquals(4, $successful);

        // Test role assignments after the upload
        $role_assignments = builder::table('role_assignments')->where('roleid', $workflow_manager_role->id)->get();
        $this->assertEquals(4, $role_assignments->count());
        // Test assigned managers
        foreach ($role_assignments as $role_assignment) {
            $this->assertArrayHasKey($role_assignment->userid, [
                $manager_users['a']->id => $manager_users['a']->username,
                $manager_users['b']->id => $manager_users['b']->username,
                $manager_users['c']->id => $manager_users['c']->username,
                $manager_users['d']->id => $manager_users['d']->username
            ]);
        }
        // Test new assignments
        $new_assignments = assignment_entity::repository()
            ->select('*')
            ->where('course', '=', $workflow->course_id)
            ->get();
        $this->assertEquals(4, $new_assignments->count());
        foreach($new_assignments as $new_assignment) {
            if ($new_assignment->id === $this->assignment->id) {
                $this->assertTrue($new_assignment->is_default);
            } else {
                $this->assertFalse($new_assignment->is_default);
            }
            $this->assertEquals(assignment_type\organisation::get_code(), $new_assignment->assignment_type);
            $this->assertEquals(status::ACTIVE, $new_assignment->status);
        }

        // Test approvers
        $workflow_version = $workflow->get_latest_version();
        $stage1_id = $workflow_version->get_stages()->first()->id;
        $stage2 = $workflow_version->get_next_stage($stage1_id);
        $approval_levels = $stage2->get_approval_levels();
        foreach ($approval_levels as $approval_level) {
            foreach ($new_assignments as $new_assignment) {
                $approvers = builder::table(assignment_approver_entity::TABLE, 'approver')
                    ->where('approval_id', $new_assignment->id)
                    ->where('workflow_stage_approval_level_id', $approval_level->id)
                    ->where('active', '=', true)
                    ->get();
                foreach ($approvers as $approver) {
                    $this->assertArrayHasKey($approver->identifier, $new_approvers);
                }
            }
        }

        return [$new_assignments, $entry, $manager_users, $approver_users];
    }

    public function test_process_data() {
        [$new_assignments, $entry, $manager_users, $approver_users] = $this->upload_and_process_assignments_csv();
        $workflow = workflow::load_by_id($this->workflow->id);
        $workflow_manager_role = $this->get_workflowmanager();

        // Upload 2
        // Re-upload same file
        $csv = csv_upload::instance($workflow->id);
        $csv->upload_csv_content($entry);
        [$successful, $errors] = $csv->process_data();

        // Test it again should be the same
        $still_role_assignments = builder::table('role_assignments')->where('roleid', $workflow_manager_role->id)->get();
        $this->assertEquals(4, $still_role_assignments->count());
        foreach ($still_role_assignments as $still_role_assignment) {
            $this->assertArrayHasKey($still_role_assignment->userid, [
                $manager_users['a']->id => $manager_users['a']->username,
                $manager_users['b']->id => $manager_users['b']->username,
                $manager_users['c']->id => $manager_users['c']->username,
                $manager_users['d']->id => $manager_users['d']->username
            ]);
        }
        // Still same
        $still_assignments = assignment_entity::repository()
            ->select('*')
            ->where('course', '=', $workflow->course_id)
            ->get();
        $this->assertEquals($still_assignments->count(), $new_assignments->count());

        // Prepare overrides with new managers and approvers
        $manager_e = \core\testing\generator::instance()->create_user(['username' => 'manager_e']);
        $manager_f = \core\testing\generator::instance()->create_user(['username' => 'manager_f']);
        $manager_g = \core\testing\generator::instance()->create_user(['username' => 'manager_g']);
        $manager_h = \core\testing\generator::instance()->create_user(['username' => 'manager_h']);

        $approver_k = \core\testing\generator::instance()->create_user(['username' => 'approver_k']);
        $approver_l = \core\testing\generator::instance()->create_user(['username' => 'approver_l']);
        $approver_m = \core\testing\generator::instance()->create_user(['username' => 'approver_m']);
        $approver_n = \core\testing\generator::instance()->create_user(['username' => 'approver_n']);
        $approver_o = \core\testing\generator::instance()->create_user(['username' => 'approver_o']);
        $approver_p = \core\testing\generator::instance()->create_user(['username' => 'approver_p']);
        $approver_r = \core\testing\generator::instance()->create_user(['username' => 'approver_r']);
        $approver_s = \core\testing\generator::instance()->create_user(['username' => 'approver_s']);
        $new_approvers = [
            $approver_users['d']->id => $approver_users['d'],
            $approver_k->id => $approver_k,
            $approver_l->id => $approver_l,
            $approver_m->id => $approver_m,
            $approver_n->id => $approver_n,
            $approver_o->id => $approver_o,
            $approver_p->id => $approver_p,
            $approver_s->id => $approver_s,
            $approver_r->id => $approver_r,
        ];

        // Upload 3
        $file =  realpath(__DIR__ . '/fixtures/assignment') . '/assignment_overrides_2.csv';
        $entry = (object)[
            'content' => file_get_contents($file),
            'delimiter' => 'comma',
            'encoding' => 'utf-8'
        ];
        $csv = csv_upload::instance($workflow->id);
        $csv->upload_csv_content($entry);
        $data = $csv->get_all_user_data();
        [$successful, $errors] = $csv->process_data();
        $this->assertEquals(4, $data['count']['total']);
        $this->assertEquals(4, $successful);

        $new_role_assignments = builder::table('role_assignments')->where('roleid', $workflow_manager_role->id)->get();
        $this->assertEquals(4, $new_role_assignments->count());
        // Test new manager
        foreach ($new_role_assignments as $role_assignment) {
            $this->assertArrayHasKey(
                $role_assignment->userid,
                [
                    $manager_e->id => $manager_e->username,
                    $manager_f->id => $manager_f->username,
                    $manager_g->id => $manager_g->username,
                    $manager_h->id => $manager_h->username
                ]
            );
        }
        // Test new approvers
        $workflow_version = $workflow->get_latest_version();

        $stage1_id = $workflow_version->get_stages()->first()->id;
        $stage2 = $workflow_version->get_next_stage($stage1_id);
        $approval_levels = $stage2->get_approval_levels();
        foreach ($approval_levels as $approval_level) {
            foreach ($new_assignments as $new_assignment) {
                $approvers = builder::table(assignment_approver_entity::TABLE, 'approver')
                    ->where('approval_id', $new_assignment->id)
                    ->where('workflow_stage_approval_level_id', $approval_level->id)
                    ->where('active', '=', true)
                    ->get();
                foreach ($approvers as $approver) {
                    $this->assertArrayHasKey($approver->identifier, $new_approvers);
                }
            }
        }
    }

    /**
     * Test uploading a subset of the possible overrides. Do not include the default assignment, for example.
     */
    public function test_selective_upload() {
        $approver_c = \core\testing\generator::instance()->create_user(['username' => 'approver_c']);
        $approver_d = \core\testing\generator::instance()->create_user(['username' => 'approver_d']);
        $approver_e = \core\testing\generator::instance()->create_user(['username' => 'approver_e']);
        $approver_f = \core\testing\generator::instance()->create_user(['username' => 'approver_f']);
        $approver_g = \core\testing\generator::instance()->create_user(['username' => 'approver_g']);
        $approver_h = \core\testing\generator::instance()->create_user(['username' => 'approver_h']);
        $manager_a = \core\testing\generator::instance()->create_user(['username' => 'manager_a']);

        $default_assignment = assignment::load_by_entity($this->assignment);

        /* @var workflow_stage $stage1 */
        $stage1 = $this->workflow->latest_version->stages->first();
        $stage2 = $this->workflow->latest_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $level2 = $stage2->approval_levels->last();

        // Put approvers c and d onto level 1 and 2 of the default assignment.
        $approver_c = assignment_approver::create(
            $default_assignment,
            $level1,
            approver_type_user::get_code(),
            $approver_c->id
        );
        $approver_c->activate();
        $approver_d = assignment_approver::create(
            $default_assignment,
            $level2,
            approver_type_user::get_code(),
            $approver_d->id
        );
        $approver_d->activate();

        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();
        $role_assignments_query = builder::table('role_assignments')
            ->join('user', 'userid', '=', 'id')
            ->where('roleid', $approver_role->id);
        $role_assignments = $role_assignments_query->get();
        $this->assertEquals(2, $role_assignments->count());

        // Process the first selective overrides csv.
        $file =  realpath(__DIR__ . '/fixtures/assignment') . '/assignment_overrides_selective_1.csv';
        $entry = (object)[
            'content' => file_get_contents($file),
            'delimiter' => 'comma',
            'encoding' => 'utf-8'
        ];
        $csv = csv_upload::instance($this->workflow->id);
        $csv->upload_csv_content($entry);
        $data = $csv->get_all_user_data();
        [$successful, $errors] = $csv->process_data();
        $this->assertEquals(3, $data['count']['total']);
        $this->assertEquals(3, $successful);

        $role_assignments = $role_assignments_query->get();
        /**
         * org: approver_c, approver_d
         * org_a: approver_c*, "approver_e,approver_f"
         * org_a_prog_a: approver_c, "approver_e*,approver_f*"
         * org_a_prob_b: approver_c*, approver_g
         */
        $role_assignments->sort('id');
        //csdebug($role_assignments);
        $this->assertEquals(10, $role_assignments->count());

        // Process the second selective overrides csv.
        $file =  realpath(__DIR__ . '/fixtures/assignment') . '/assignment_overrides_selective_2.csv';
        $entry = (object)[
            'content' => file_get_contents($file),
            'delimiter' => 'comma',
            'encoding' => 'utf-8'
        ];
        $csv = csv_upload::instance($this->workflow->id);
        $csv->upload_csv_content($entry);
        $data = $csv->get_all_user_data();
        [$successful, $errors] = $csv->process_data();
        $this->assertEquals(3, $data['count']['total']);
        $this->assertEquals(3, $successful);
        $role_assignments = $role_assignments_query->get();
        /**
         * org: approver_c, approver_d
         * org_a: approver_e, approver_g
         * org_a_prog_a: approver_e*, approver_g*
         * org_a_prog_b: approver_f, approver_h
         */
        $assignments_repository = assignment_entity::repository();
        $this->assertEquals(8, $role_assignments->count());
    }

    public function test_remove_via_csv() {
        [$new_assignments, $entry, $manager_users, $approver_users] = $this->upload_and_process_assignments_csv();
        $workflow = workflow::load_by_id($this->workflow->id);
        $workflow_manager_role = $this->get_workflowmanager();

        // Upload 2 - remove
        $new_approvers = [
            $approver_users['d']->id => $approver_users['d'],
            $approver_users['e']->id => $approver_users['e'],
            $approver_users['f']->id => $approver_users['f'],
            $approver_users['g']->id => $approver_users['g'],
            $approver_users['h']->id => $approver_users['h'],
        ];

        $file =  realpath(__DIR__ . '/fixtures/assignment') . '/assignment_overrides_remove.csv';
        $entry = (object)[
            'content' => file_get_contents($file),
            'delimiter' => 'comma',
            'encoding' => 'utf-8'
        ];
        $csv = csv_upload::instance($workflow->id);
        $csv->upload_csv_content($entry);
        $data = $csv->get_all_user_data();
        [$successful, $errors] = $csv->process_data();
        $this->assertEquals(2, $data['count']['total']);
        $this->assertEquals(2, $successful);

        // Test role assignments after the upload
        $role_assignments = builder::table('role_assignments')->where('roleid', $workflow_manager_role->id)->get();
        $this->assertEquals(3, $role_assignments->count());
        // Test assigned managers, manager_a was removed leaving b, c, d.
        foreach ($role_assignments as $role_assignment) {
            $this->assertArrayHasKey($role_assignment->userid, [
                $manager_users['b']->id => $manager_users['b']->username,
                $manager_users['c']->id => $manager_users['c']->username,
                $manager_users['d']->id => $manager_users['d']->username
            ]);
        }
        // Test assignments - same as before.
        $new_assignments = assignment_entity::repository()
            ->select('*')
            ->where('course', '=', $workflow->course_id)
            ->get();
        $this->assertEquals(4, $new_assignments->count());
        foreach($new_assignments as $new_assignment) {
            if ($new_assignment->id === $this->assignment->id) {
                $this->assertTrue($new_assignment->is_default);
            } else {
                $this->assertFalse($new_assignment->is_default);
            }
            $this->assertEquals(assignment_type\organisation::get_code(), $new_assignment->assignment_type);
            $this->assertEquals(status::ACTIVE, $new_assignment->status);
        }

        // Test approvers - approvers c, i, j were removed.
        $workflow_version = $workflow->get_latest_version();
        $workflow_stages = $workflow_version->get_stages();
        foreach ($workflow_stages as $workflow_stage) {
            $approval_levels = $workflow_stage->get_approval_levels();
            foreach ($approval_levels as $approval_level) {
                foreach ($new_assignments as $new_assignment) {
                    $approvers = builder::table(assignment_approver_entity::TABLE, 'approver')
                        ->where('approval_id', $new_assignment->id)
                        ->where('workflow_stage_approval_level_id', $approval_level->id)
                        ->where('active', '=', true)
                        ->get();
                    foreach ($approvers as $approver) {
                        $this->assertArrayHasKey($approver->identifier, $new_approvers);
                    }
                }
            }
        }
    }

    public function test_remove_and_replace_on_inherited_assignment() {
        [$new_assignments, $entry, $manager_users, $approver_users] = $this->upload_and_process_assignments_csv();
        $workflow = workflow::load_by_id($this->workflow->id);
        $default_assignment = assignment::load_by_entity($this->assignment);
        $workflow_manager_role = $this->get_workflowmanager();

        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        $approval_level_1 = $stage2->approval_levels->first();
        $approval_level_2 = $stage2->approval_levels->last();

        /** @var $org_a_prog_a assignment */
        /** @var $org_a_prog_b assignment */
        foreach ($new_assignments as $assignment_entity) {
            $assignment = assignment::load_by_entity($assignment_entity);
            if (in_array($assignment->assigned_to->shortname, ['org_a_prog_a', 'org_a_prog_b'])) {
                ${$assignment->assigned_to->shortname} = $assignment;
            }
        }

        // Upload 2 - replace
        $file =  realpath(__DIR__ . '/fixtures/assignment') . '/assignment_overrides_replace.csv';
        $entry = (object)[
            'content' => file_get_contents($file),
            'delimiter' => 'comma',
            'encoding' => 'utf-8'
        ];
        $csv = csv_upload::instance($workflow->id);
        $csv->upload_csv_content($entry);
        $data = $csv->get_all_user_data();
        [$successful, $errors] = $csv->process_data();
        $this->assertEquals(3, $data['count']['total']);
        $this->assertEquals(3, $successful);

        // Check that approver_e was removed from default assignment level 1.
        $default_assignment_approval_level_1 = new assignment_approval_level($default_assignment, $approval_level_1);
        $this->assertCount(0, $default_assignment_approval_level_1->get_approvers());

        // Check that approver_e was added to org_a_prog_a level 1.
        $org_a_prog_a_approval_level_1 = new assignment_approval_level($org_a_prog_a, $approval_level_1);
        $approvers = $org_a_prog_a_approval_level_1->get_approvers();
        $this->assertCount(1, $approvers);
        $this->assertEquals('approver_e', $approvers[0]->approver_entity->username);

        // Check that approver_f was added to org_a_prog_b level 2.
        $org_a_prog_b_approval_level_2 = new assignment_approval_level($org_a_prog_b, $approval_level_2);
        $approvers = $org_a_prog_b_approval_level_2->get_approvers();
        $this->assertCount(1, $approvers);
        $this->assertEquals('approver_f', $approvers[0]->approver_entity->username);
    }
}