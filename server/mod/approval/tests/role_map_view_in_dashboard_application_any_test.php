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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use container_approval\approval;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\data_provider\application\role_map\view_in_dashboard_application_any;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_role_map_view_in_dashboard_application_any_testcase extends testcase {

    use approval_workflow_test_setup;

    public const MAP_TABLE = 'approval_role_capability_map';

    public const MAP_CAPABILITY = 'mod/approval:view_in_dashboard_application_any';

    public const ROLES_DEFAULT = 3;

    private function remove_default_role_assignments() {
        $category_context = approval::get_default_category_context();

        $managerid = builder::table('role')->where('shortname', '=', 'manager')->one(true)->id;
        $workflowmanagerid = builder::table('role')->where('shortname', '=', 'approvalworkflowmanager')->one(true)->id;
        $workflowapproverid = builder::table('role')->where('shortname', '=', 'approvalworkflowapprover')->one(true)->id;

        assign_capability(self::MAP_CAPABILITY, CAP_PREVENT, $managerid, $category_context, true);
        assign_capability(self::MAP_CAPABILITY, CAP_PREVENT, $workflowmanagerid, $category_context, true);
        assign_capability(self::MAP_CAPABILITY, CAP_PREVENT, $workflowapproverid, $category_context, true);
    }

    public function test_recalculate_complete_map() {
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();

        $map = new view_in_dashboard_application_any();

        $this->assertEquals(0, builder::table(self::MAP_TABLE)->count());

        $map->recalculate_complete_map();

        // This number will depend on how many roles have this capability by default!
        $this->assertEquals(self::ROLES_DEFAULT * 2, builder::table(self::MAP_TABLE)->count());

        $managerid = builder::table('role')->where('shortname', '=', 'manager')->one(true)->id;
        $workflowmanagerid = builder::table('role')->where('shortname', '=', 'approvalworkflowmanager')->one(true)->id;
        $workflowapproverid = builder::table('role')->where('shortname', '=', 'approvalworkflowapprover')->one(true)->id;

        $actual = builder::table(self::MAP_TABLE)
            ->join(['course_modules', 'cm'], 'instanceid', '=', 'id')
            ->join(['approval', 'assignment'], 'cm.instance', '=', 'id')
            ->select(['roleid', 'assignment.id'])
            ->get(true)
            ->to_array();
        $expected = [
            ['roleid' => $managerid, 'id' => $assignment1->id],
            ['roleid' => $workflowmanagerid, 'id' => $assignment1->id],
            ['roleid' => $workflowapproverid, 'id' => $assignment1->id],
            ['roleid' => $managerid, 'id' => $assignment2->id],
            ['roleid' => $workflowmanagerid, 'id' => $assignment2->id],
            ['roleid' => $workflowapproverid, 'id' => $assignment2->id],
        ];
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function test_recalculate_map_for_instance() {
        list($workflow_entity1, , $assignment_entity1) = $this->create_workflow_and_assignment();
        list($workflow_entity2, , $assignment_entity2) = $this->create_workflow_and_assignment();
        $workflow1 = workflow::load_by_entity($workflow_entity1);
        $workflow2 = workflow::load_by_entity($workflow_entity2);
        $assignment1 = assignment::load_by_entity($assignment_entity1);
        $assignment2 = assignment::load_by_entity($assignment_entity2);

        // Remove default assignments
        $this->remove_default_role_assignments();

        // Make a role assignment at each workflow
        $role1id = $this->getDataGenerator()->create_role();
        assign_capability(self::MAP_CAPABILITY, CAP_ALLOW, $role1id, $workflow1->get_context(), true);
        assign_capability(self::MAP_CAPABILITY, CAP_ALLOW, $role1id, $workflow2->get_context(), true);

        // Calculate the complete map first.
        $map = new view_in_dashboard_application_any();
        $map->recalculate_complete_map();

        $this->assertEquals(2, builder::table(self::MAP_TABLE)->count());

        // Assign new role for each workflow.
        $role2id = $this->getDataGenerator()->create_role();
        assign_capability(self::MAP_CAPABILITY, CAP_ALLOW, $role2id, $workflow1->get_context(), true);
        assign_capability(self::MAP_CAPABILITY, CAP_ALLOW, $role2id, $workflow2->get_context(), true);

        // There are two assignments with new roles in the map, but only update assignment1.
        $map->recalculate_map_for_instance($assignment1->get_context()->instanceid);
        $this->assertEquals(3, builder::table(self::MAP_TABLE)->count());

        $actual = builder::table(self::MAP_TABLE)
            ->join(['course_modules', 'cm'], 'instanceid', '=', 'id')
            ->join(['approval', 'assignment'], 'cm.instance', '=', 'id')
            ->select(['roleid', 'assignment.id'])
            ->get(true)
            ->to_array();
        $expected = [
            ['roleid' => $role1id, 'id' => $assignment1->id],
            ['roleid' => $role2id, 'id' => $assignment1->id],
            ['roleid' => $role1id, 'id' => $assignment2->id],
        ];
        $this->assertEqualsCanonicalizing($expected, $actual);

        // Now update all.
        $map->recalculate_complete_map();
        $this->assertEquals(4, builder::table(self::MAP_TABLE)->count());

        $actual = builder::table(self::MAP_TABLE)
            ->join(['course_modules', 'cm'], 'instanceid', '=', 'id')
            ->join(['approval', 'assignment'], 'cm.instance', '=', 'id')
            ->select(['roleid', 'assignment.id'])
            ->get(true)
            ->to_array();
        $expected = [
            ['roleid' => $role1id, 'id' => $assignment1->id],
            ['roleid' => $role2id, 'id' => $assignment1->id],
            ['roleid' => $role1id, 'id' => $assignment2->id],
            ['roleid' => $role2id, 'id' => $assignment2->id],
        ];
        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}