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
use mod_approval\data_provider\application\role_map\role_map_base;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\data_provider\application\role_map\view_in_dashboard_application_any;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @coversDefaultClass \mod_approval\data_provider\application\role_map\role_map_controller
 *
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_role_map_controller_testcase extends testcase {

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
     * @covers ::map_classes
     */
    public function test_map_classes() {
        $classes = role_map_controller::map_classes();
        $this->assertEqualsCanonicalizing(self::ALL_CLASSES, $classes);
    }

    /**
     * @covers ::get_all_maps
     */
    public function test_get_all_maps() {
        $maps = role_map_controller::get_all_maps();
        $this->assertCount(count(self::ALL_CLASSES), $maps);
        foreach ($maps as $map) {
            $this->assertInstanceOf(role_map_base::class, $map);
        }
    }

    /**
     * @covers ::get
     */
    public function test_get() {
        foreach (self::ALL_CAPABILITIES as $ix => $capability) {
            $map = role_map_controller::get($capability);
            $this->assertInstanceOf(self::ALL_CLASSES[$ix], $map);
        }
    }

    /**
     * @covers ::get
     */
    public function test_get_with_short_capability() {
        $map = role_map_controller::get('view_in_dashboard_application_any');
        $this->assertInstanceOf(view_in_dashboard_application_any::class, $map);
    }

    /**
     * @covers ::get
     */
    public function test_get_with_unknown_capability() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown role map controller capability');
        $map = role_map_controller::get('mod/approval:cap_doesnt_exist');
    }

    /**
     * @covers ::get
     */
    public function test_get_with_wrong_capability() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown role map controller capability');
        $map = role_map_controller::get('mod/approval:create_application_any');
    }

    /**
     * @covers ::regenerate_all_maps
     */
    public function test_regenerate_all_maps() {
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();

        $this->assertEquals(0, builder::table(self::MAP_TABLE)->count());

        role_map_controller::regenerate_all_maps();

        // This number will depend on how many roles have this capability by default!
        $this->assertEquals(self::ALL_ROLES_DEFAULT, builder::table(self::MAP_TABLE)->count());

        $this->remove_default_role_assignments();

        // Maps have not been regenerated...
        $this->assertEquals(self::ALL_ROLES_DEFAULT, builder::table(self::MAP_TABLE)->count());

        role_map_controller::regenerate_all_maps();
        $this->assertEquals((self::ALL_ROLES_DEFAULT - self::MAP_ROLES_DEFAULT), builder::table(self::MAP_TABLE)->count());
    }
}