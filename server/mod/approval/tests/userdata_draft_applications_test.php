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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use core\entity\user as user_entity;
use approvalform_simple\installer;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\application\application as application_entity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\workflow;
use mod_approval\userdata\applicant_draft_applications as draft_applications;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\userdata\applicant_draft_applications
 */
class mod_approval_userdata_draft_applications_testcase extends testcase {

    protected $workflow;
    /** @var user_entity $applicant */
    protected $applicant;
    /** @var target_user $target_user */
    protected $target_user;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        global $DB;
        $this->setAdminUser();
        $installer = new installer();
        $transaction = $DB->start_delegated_transaction();
        $cohort = $installer->install_demo_cohort();
        $workflow_entity = $installer->install_demo_workflow($cohort, 'Simple', true);
        list($applicant, $ja) = $installer->install_demo_assignment($cohort);
        $installer->install_demo_applications($workflow_entity, $applicant, $ja);
        $transaction->allow_commit();

        $user = core_user::get_user_by_username('sellison');
        $this->target_user = new target_user($user);
        $this->applicant = new user_entity($user->id);

        $this->workflow = workflow::load_by_id($workflow_entity->id);

        parent::setUp();
    }

    protected function tearDown(): void {
        $this->workflow = null;
        $this->applicant = null;
        $this->target_user = null;
        parent::tearDown();
    }

    public function test_count() {
        $assignment = assignment::load_by_id($this->workflow->default_assignment->id);

        // Test module context
        $context_module = $assignment->get_context();
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_module));

        // Test system context
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, context_system::instance()));

        // Test course context
        $context_course = $this->workflow->get_context();
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_course));

        // Test course category context
        $course = get_course($this->workflow->course_id);
        $context_coursecat = context_coursecat::instance($course->category);
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_coursecat));
    }

    public function test_export() {
        $assignment = assignment::load_by_id($this->workflow->default_assignment->id);
        $application = application_entity::repository()
            ->where('is_draft', '=', 1)
            ->order_by('id')
            ->get()
            ->map_to(application::class)
            ->first();

        // Test module context
        $context_module = $assignment->get_context();
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_module));

        $export = draft_applications::execute_export($this->target_user, $context_module);
        $data = $export->data;
        usort($data, function ($x, $y) {
            return $x->id <=> $y->id;
        });
        $this->assertCount(3, $data);
        //compare first application in DRAFT condition
        $this->assertEquals($application->id, $data[0]->id);

        // Test system context
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, context_system::instance()));
        $export = draft_applications::execute_export($this->target_user, context_system::instance());
        $data = $export->data;
        $this->assertCount(3, $data);

        // Test course context
        $context_course = $this->workflow->get_context();
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_course));
        $export = draft_applications::execute_export($this->target_user, $context_course);
        $data = $export->data;
        $this->assertCount(3, $data);

        // Test course category context
        $course = get_course($this->workflow->course_id);
        $context_coursecat = context_coursecat::instance($course->category);
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_coursecat));
        $export = draft_applications::execute_export($this->target_user, $context_coursecat);
        $data = $export->data;
        $this->assertCount(3, $data);
    }

    public function test_purge_module_context() {
        $assignment = assignment::load_by_id($this->workflow->default_assignment->id);
        $applications = $assignment->get_applications()->all();

        // Test module context
        $context_module = $assignment->get_context();
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_module));

        /** @var application[] $applications */
        $status = draft_applications::execute_purge($this->target_user, $context_module);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assertEquals(
            0,
            application_entity::repository()
                ->where('id', '=', $applications[4]->id)
                ->or_where('id', '=', $applications[5]->id)
                ->or_where('id', '=', $applications[6]->id)
                ->count()
        );
    }

    public function test_purge_course_context() {
        $context_course = $this->workflow->get_context();
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_course));

        $status = draft_applications::execute_purge($this->target_user, $context_course);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(
            0,
            application_entity::repository()
                ->where('user_id', '=', $this->applicant->id)
                ->where('is_draft', '=', 1)
                ->count()
        );
    }

    public function test_purge_category_context() {
        $course = get_course($this->workflow->course_id);
        $context_coursecat = context_coursecat::instance($course->category);
        $this->assertEquals(3, draft_applications::execute_count($this->target_user, $context_coursecat));

        $status = draft_applications::execute_purge($this->target_user, $context_coursecat);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(
            0,
            application_entity::repository()
                ->where('user_id', '=', $this->applicant->id)
                ->where('is_draft', '=', 1)
                ->count()
        );
    }

    public function test_purge_system_context() {

        $this->assertEquals(3, draft_applications::execute_count($this->target_user, context_system::instance()));

        $status = draft_applications::execute_purge($this->target_user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(
            0,
            application_entity::repository()
                ->where('user_id', '=', $this->applicant->id)
                ->where('is_draft', '=', 1)
                ->count()
        );
    }
}