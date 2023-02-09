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
use core\entity\user;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\data_provider\application\capability_map\capability_map_base;
use mod_approval\data_provider\application\capability_map\capability_map_controller;
use mod_approval\data_provider\application\capability_map\view_in_dashboard_application_any;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_approver_generator_object;

/**
 * @coversDefaultClass \mod_approval\data_provider\application\capability_map\capability_map_controller
 *
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_capability_map_controller_testcase extends testcase {

    use approval_workflow_test_setup;

    public const ALL_CLASSES = [
        'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_any',
        'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_applicant',
        'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_user',
        'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_any',
        'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_applicant',
        'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_user',
        'mod_approval\data_provider\application\capability_map\view_in_dashboard_pending_application_any',
        'mod_approval\data_provider\application\capability_map\view_in_dashboard_pending_application_user',
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

    public const MAP_TABLE = 'approval_dashboard_application_any';

    public const MAP_CAPABILITY = 'mod/approval:view_in_dashboard_application_any';

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
     * @covers ::map_classes
     */
    public function test_map_classes() {
        $classes = capability_map_controller::map_classes();
        $this->assertEqualsCanonicalizing(self::ALL_CLASSES, $classes);
    }

    /**
     * @covers ::get_all_maps
     */
    public function test_get_all_maps() {
        $maps = capability_map_controller::get_all_maps($this->getDataGenerator()->create_user()->id);
        $this->assertCount(count(self::ALL_CLASSES), $maps);
        foreach ($maps as $map) {
            $this->assertInstanceOf(capability_map_base::class, $map);
        }
    }

    /**
     * @covers ::get
     */
    public function test_get() {
        foreach (self::ALL_CAPABILITIES as $ix => $capability) {
            $map = capability_map_controller::get($capability, $this->getDataGenerator()->create_user()->id);
            $this->assertInstanceOf(self::ALL_CLASSES[$ix], $map);
        }
    }

    /**
     * @covers ::get
     */
    public function test_get_with_short_capability() {
        $map = capability_map_controller::get('view_in_dashboard_application_any', $this->getDataGenerator()->create_user()->id);
        $this->assertInstanceOf(view_in_dashboard_application_any::class, $map);
    }

    /**
     * @covers ::get
     */
    public function test_get_with_unknown_capability() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Tried to get unknown capability map');
        $map = capability_map_controller::get('mod/approval:cap_doesnt_exist', $this->getDataGenerator()->create_user()->id);
    }

    /**
     * @covers ::get
     */
    public function test_get_with_wrong_capability() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Tried to get unknown capability map');
        $map = capability_map_controller::get('mod/approval:create_application_any', $this->getDataGenerator()->create_user()->id);
    }

    /**
     * @covers ::regenerate_all_maps
     */
    public function test_regenerate_all_maps() {
        list($workflow_entity1, , $assignment1) = $this->create_workflow_and_assignment();
        $workflow1 = workflow::load_by_entity($workflow_entity1);
        $w1_stage1 = $workflow1->latest_version->stages->first();
        $w1_stage2 = $workflow1->latest_version->get_next_stage($w1_stage1->id);
        $w1_approval_level1 = $w1_stage2->approval_levels->first();

        list($workflow_entity2, , $assignment2) = $this->create_workflow_and_assignment();
        $workflow2 = workflow::load_by_entity($workflow_entity2);
        $w2_stage1 = $workflow2->latest_version->stages->first();
        $w2_stage2 = $workflow2->latest_version->get_next_stage($w2_stage1->id);
        $w2_approval_level1 = $w2_stage2->approval_levels->first();

        role_map_controller::regenerate_all_maps();

        // Create a user and add as an approver on assignment2.
        $approver_user = new user($this->getDataGenerator()->create_user()->id);
        $approver_go = new assignment_approver_generator_object(
            $assignment2->id,
            $w2_approval_level1->id,
            \mod_approval\model\assignment\approver_type\user::get_code(),
            $approver_user->id
        );
        $assignment_approver_entity2 = $this->generator()->create_assignment_approver($approver_go);
        $assignment_approver2 = assignment_approver::load_by_entity($assignment_approver_entity2);
        $assignment_approver2->activate();

        // No maps yet.
        $this->assertEquals(0, builder::table(self::MAP_TABLE)->count());

        capability_map_controller::regenerate_all_maps($approver_user->id);

        // Approver has the capability in one assignment.
        $this->assertEquals(1, builder::table(self::MAP_TABLE)->count());

        // Add user as approver on assignment1.
        $approver_go->approval_id = $assignment1->id;
        $approver_go->workflow_stage_approval_level_id = $w1_approval_level1->id;
        $assignment_approver_entity1 = $this->generator()->create_assignment_approver($approver_go);
        $assignment_approver1 = assignment_approver::load_by_entity($assignment_approver_entity1);
        $assignment_approver1->activate();

        // Maps have not been regenerated...
        $this->assertEquals(1, builder::table(self::MAP_TABLE)->count());

        capability_map_controller::regenerate_all_maps($approver_user->id);

        // Now both assignments have map entries.
        $this->assertEquals(2, builder::table(self::MAP_TABLE)->count());

        // Change the role maps and regenerate again.
        $this->remove_default_role_assignments();
        role_map_controller::regenerate_all_maps();
        capability_map_controller::regenerate_all_maps($approver_user->id);

        // No more map entries please.
        $this->assertEquals(0, builder::table(self::MAP_TABLE)->count());
    }
}