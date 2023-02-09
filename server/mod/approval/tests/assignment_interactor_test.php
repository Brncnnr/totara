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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\interactor\assignment_interactor;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\assignment_type\organisation as organisation_assignment_type;
use mod_approval\model\status;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\generator;
use totara_job\job_assignment;
use totara_hierarchy\testing\generator as hierarchy_generator;

/**
 * @group approval_workflow
 */
class mod_approval_assignment_interactor_testcase extends testcase {

    private function get_assignment_activity_context(): context_module {
        $generator = generator::instance();
        $workflow = $generator->create_simple_request_workflow('Testing', 'Simple Request Workflow', false);
        $hierarchy_generator = hierarchy_generator::instance();
        $framework = $hierarchy_generator->create_framework('organisation');
        $organisation = $hierarchy_generator->create_org(['frameworkid' => $framework->id]);

        $assignment_model = assignment_model::create(
            $workflow->course_id,
            organisation_assignment_type::get_code(),
            $organisation->id
        );

        return $assignment_model->get_context();
    }

    public function test_workflow_interactor_roles_have_correct_defaults() {
        $assignment_activity_context = $this->get_assignment_activity_context();

        $applicant_user = self::getDataGenerator()->create_user();

        // Some other random user in relation to the applicant.
        $random_user = self::getDataGenerator()->create_user();
        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $random_user->id);
        self::assertFalse($interactor->can_create_application());

        // Normal user in relation to themselves.
        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $applicant_user->id);
        self::assertTrue($interactor->can_create_application());

        // A manager in relation to one of their staff.
        $manager_user = self::getDataGenerator()->create_user();
        $manager_job = job_assignment::create_default($manager_user->id);
        job_assignment::create_default($applicant_user->id, ['managerjaid' => $manager_job->id]);
        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $manager_user->id);
        self::assertFalse($interactor->can_create_application());

        // An approver in the assignment activity context.
        $approver_user = self::getDataGenerator()->create_user();
        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();
        role_assign($approver_role->id, $approver_user->id, $assignment_activity_context);
        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $approver_user->id);
        self::assertFalse($interactor->can_create_application());

        // A workflow manager in the assignment activity context.
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $assignment_activity_context);
        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $workflow_manager_user->id);
        self::assertTrue($interactor->can_create_application());

        // A site manager in the assignment activity context.
        $site_manager_user = self::getDataGenerator()->create_user();
        $site_manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($site_manager_role->id, $site_manager_user->id, $assignment_activity_context);
        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $site_manager_user->id);
        self::assertTrue($interactor->can_create_application());

        // Tenant domain manager?
    }

    public function test_can_create_application_matches_capabilities() {
        $assignment_activity_context = $this->get_assignment_activity_context();
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $interactor_user = self::getDataGenerator()->create_user();
        $applicant_user = self::getDataGenerator()->create_user();
        $applicant_user_context = context_user::instance($applicant_user->id);

        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $applicant_user->id);

        // Test that a user without any cap cannot interact.
        assign_capability(
            'mod/approval:create_application_applicant',
            CAP_PREVENT,
            $user_role->id,
            $assignment_activity_context,
            true
        );
        self::assertFalse($interactor->can_create_application());

        // Test that a user with the 'own' capability can interact.
        assign_capability(
            'mod/approval:create_application_applicant',
            CAP_ALLOW,
            $user_role->id,
            $assignment_activity_context,
            true
        );
        self::assertTrue($interactor->can_create_application());
        assign_capability(
            'mod/approval:create_application_applicant',
            CAP_PREVENT,
            $user_role->id,
            $assignment_activity_context,
            true
        );

        $interactor = new assignment_interactor($assignment_activity_context, $applicant_user->id, $interactor_user->id);

        // Test 'user' capability in the applicant user context.
        assign_capability('mod/approval:create_application_user', CAP_ALLOW, $user_role->id, $applicant_user_context, true);
        self::assertTrue($interactor->can_create_application());
        assign_capability('mod/approval:create_application_user', CAP_PREVENT, $user_role->id, $applicant_user_context, true);

        // Test 'any' capability in the assignment's activity context.
        assign_capability('mod/approval:create_application_any', CAP_ALLOW, $user_role->id, $assignment_activity_context, true);
        self::assertTrue($interactor->can_create_application());
    }
}
