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
 * @package container_approval
 */

defined('MOODLE_INTERNAL') || die();

use container_approval\approval as container_approval;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\interactor\category_interactor;
use totara_tenant\testing\generator as tenant_generator;

/**
 * @group approval_workflow
 */
class mod_approval_category_interactor_testcase extends testcase {

    public function test_category_interactor_roles_have_correct_defaults() {
        // Normal user already has the authenticated user role.
        $user = self::getDataGenerator()->create_user();

        $interactor = new category_interactor(container_approval::get_default_category_context(), $user->id);

        self::assertFalse($interactor->can_create_workflow_from_template());
        self::assertFalse($interactor->can_create_workflow());
        self::assertFalse($interactor->has_clone_workflow_capability());
        self::assertFalse($interactor->can_create_workflow_template());
        self::assertFalse($interactor->can_move_application_between_workflows());
        self::assertFalse($interactor->can_manage_workflows());

        // Assign staff manager role in the system context (just using system because it is easy and covers all users).
        $staff_manager = self::getDataGenerator()->create_user();
        $staff_manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();
        role_assign($staff_manager_role->id, $staff_manager->id, context_system::instance());

        $interactor = new category_interactor(container_approval::get_default_category_context(), $staff_manager->id);

        self::assertFalse($interactor->can_create_workflow_from_template());
        self::assertFalse($interactor->can_create_workflow());
        self::assertFalse($interactor->has_clone_workflow_capability());
        self::assertFalse($interactor->can_create_workflow_template());
        self::assertFalse($interactor->can_move_application_between_workflows());
        self::assertFalse($interactor->can_manage_workflows());

        // Assign approver role in the system context.
        $approver = self::getDataGenerator()->create_user();
        $approver_role = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one();
        role_assign($approver_role->id, $approver->id, context_system::instance());

        $interactor = new category_interactor(container_approval::get_default_category_context(), $approver->id);

        self::assertFalse($interactor->can_create_workflow_from_template());
        self::assertFalse($interactor->can_create_workflow());
        self::assertFalse($interactor->has_clone_workflow_capability());
        self::assertFalse($interactor->can_create_workflow_template());
        self::assertFalse($interactor->can_move_application_between_workflows());
        self::assertFalse($interactor->can_manage_workflows());

        // Assign editing trainer role in the system context.
        $workflow_manager = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager->id, context_system::instance());

        $interactor = new category_interactor(container_approval::get_default_category_context(), $workflow_manager->id);

        self::assertFalse($interactor->can_create_workflow_from_template());
        self::assertFalse($interactor->can_create_workflow());
        self::assertFalse($interactor->has_clone_workflow_capability());
        self::assertFalse($interactor->can_create_workflow_template());
        self::assertFalse($interactor->can_move_application_between_workflows());
        self::assertFalse($interactor->can_manage_workflows());

        // Assign site manager role in the system context.
        $site_manager = self::getDataGenerator()->create_user();
        $site_manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($site_manager_role->id, $site_manager->id, context_system::instance());

        $interactor = new category_interactor(container_approval::get_default_category_context(), $site_manager->id);

        self::assertTrue($interactor->can_create_workflow_from_template());
        self::assertTrue($interactor->can_create_workflow());
        self::assertTrue($interactor->has_clone_workflow_capability());
        self::assertTrue($interactor->can_create_workflow_template());
        self::assertTrue($interactor->can_move_application_between_workflows());
        self::assertTrue($interactor->can_manage_workflows());

        // Assign tenant domain manager role in the system context.
        $tenant_generator = tenant_generator::instance();
        $tenant_generator->enable_tenants();
        $tenant_manager = self::getDataGenerator()->create_user();
        $tenant_manager_role = builder::table('role')->where('shortname', 'tenantdomainmanager')->one();
        role_assign($tenant_manager_role->id, $tenant_manager->id, context_system::instance());

        $interactor = new category_interactor(container_approval::get_default_category_context(), $tenant_manager->id);

        self::assertTrue($interactor->can_create_workflow_from_template());
        self::assertTrue($interactor->can_create_workflow());
        self::assertTrue($interactor->has_clone_workflow_capability());
        self::assertTrue($interactor->can_create_workflow_template());
        self::assertTrue($interactor->can_move_application_between_workflows());
        self::assertTrue($interactor->can_manage_workflows());
    }

    public function test_functions_match_capabilities() {
        $functions_capabilities = [
            'can_create_workflow_from_template' => 'create_workflow_from_template',
            'can_create_workflow' => 'create_workflow',
            'has_clone_workflow_capability' => 'clone_workflow',
            'can_create_workflow_template' => 'create_workflow_template',
            'can_move_application_between_workflows' => 'move_application_between_workflows',
            'can_manage_workflows' => 'manage_workflows'
        ];

        // Normal user already has the authenticated user role, and this role has none of the caps.
        $user = self::getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->one();

        $interactor = new category_interactor(container_approval::get_default_category_context(), $user->id);

        $system_context = context_system::instance();

        foreach ($functions_capabilities as $function => $capability) {
            // Test that a user without the cap cannot interact.
            self::assertFalse($interactor->$function());

            // Add capability to the role.
            assign_capability('mod/approval:' . $capability, CAP_ALLOW, $user_role->id, $system_context, true);

            // Test that a user with the cap can interact.
            self::assertTrue($interactor->$function());

            // Remove capability from the role (avoid contamination between test cases).
            assign_capability('mod/approval:' . $capability, CAP_PREVENT, $user_role->id, $system_context, true);
        }
    }
}
