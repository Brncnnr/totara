<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use container_approval\approval;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\task\role_map_regenerate_all;
use mod_approval\testing\approval_workflow_test_setup;


/**
 * @coversDefaultClass \mod_approval\task\role_map_regenerate_all
 *
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_task_role_map_regenerate_all_testcase extends testcase {

    use approval_workflow_test_setup;

    public const ALL_CLASSES = [
        'mod_approval\data_provider\application\role_map\view_draft_in_dashboard_application_any',
        'mod_approval\data_provider\application\role_map\view_draft_in_dashboard_application_applicant',
        'mod_approval\data_provider\application\role_map\view_draft_in_dashboard_application_user',
        'mod_approval\data_provider\application\role_map\view_in_dashboard_application_any',
        'mod_approval\data_provider\application\role_map\view_in_dashboard_application_applicant',
        'mod_approval\data_provider\application\role_map\view_in_dashboard_application_user',
        'mod_approval\data_provider\application\role_map\view_in_dashboard_pending_application_any',
        'mod_approval\data_provider\application\role_map\view_in_dashboard_pending_application_user',
    ];

    public const ALL_CAPABILITIES = [
        'mod/approval:view_draft_in_dashboard_application_any',
        'mod/approval:view_draft_in_dashboard_application_applicant',
        'mod/approval:view_draft_in_dashboard_application_user',
        'mod/approval:view_in_dashboard_application_any',
        'mod/approval:view_in_dashboard_application_applicant',
        'mod/approval:view_in_dashboard_application_user',
        'mod/approval:view_in_dashboard_pending_application_any',
        'mod/approval:view_in_dashboard_pending_application_user',
    ];

    public const MAP_TABLE = 'approval_role_capability_map';

    public const MAP_CAPABILITY = 'mod/approval:view_in_dashboard_application_any';

    public const MAP_ROLES_DEFAULT = 6;

    public const ALL_ROLES_DEFAULT = 10;

    private function remove_default_role_assignments() {
        $category_context = approval::get_default_category_context();

        $managerid = builder::table('role')->where('shortname', '=', 'manager')->one(true)->id;
        $workflowmanagerid = builder::table('role')->where('shortname', '=', 'approvalworkflowmanager')->one(true)->id;
        $workflowapproverid = builder::table('role')->where('shortname', '=', 'approvalworkflowapprover')->one(true)->id;

        assign_capability(self::MAP_CAPABILITY, CAP_PREVENT, $managerid, $category_context, true);
        assign_capability(self::MAP_CAPABILITY, CAP_PREVENT, $workflowmanagerid, $category_context, true);
        assign_capability(self::MAP_CAPABILITY, CAP_PREVENT, $workflowapproverid, $category_context, true);
    }

    /**
     * @covers ::execute
     */
    public function test_regenerate_all_maps() {
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();

        $this->assertEquals(0, builder::table(self::MAP_TABLE)->count());

        $task = new role_map_regenerate_all();
        $task->execute();

        // This number will depend on how many roles have this capability by default!
        $this->assertEquals(self::ALL_ROLES_DEFAULT, builder::table(self::MAP_TABLE)->count());

        $this->remove_default_role_assignments();

        // Maps have not been regenerated...
        $this->assertEquals(self::ALL_ROLES_DEFAULT, builder::table(self::MAP_TABLE)->count());

        $task1 = new role_map_regenerate_all();
        $task1->execute();
        $this->assertEquals((self::ALL_ROLES_DEFAULT - self::MAP_ROLES_DEFAULT), builder::table(self::MAP_TABLE)->count());

        // Do it again.
        $task1->execute();
        $this->assertEquals((self::ALL_ROLES_DEFAULT - self::MAP_ROLES_DEFAULT), builder::table(self::MAP_TABLE)->count());
    }
}