<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 * @category test
 */

use core\entity\user;
use core\orm\query\builder;
use totara_evidence\entity\evidence_type;
use totara_evidence\models\evidence_item;
use totara_evidence\models\helpers\evidence_item_capability_helper;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_capabilities_testcase extends totara_evidence_testcase {

    /**
     * Test that the manage and view capabilities work as intended for Authenticated users
     */
    public function test_user_capabilities(): void {
        $data = $this->create_test_data();
        self::setUser($data->staff_user->id);
        $role = builder::table('role')->where('shortname', 'user')->value('id');
        $context = context_system::instance();

        unassign_capability('totara/evidence:viewanyevidenceonself', $role);
        unassign_capability('totara/evidence:manageanyevidenceonself', $role);
        unassign_capability('totara/evidence:manageownevidenceonself', $role);
        $this->assertFalse($data->staff_evidence_by_staff->can_modify());
        $this->assertFalse($data->staff_evidence_by_manager->can_modify());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->manager_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->manager_user->id)->can_create());

        assign_capability('totara/evidence:viewanyevidenceonself', CAP_ALLOW, $role, $context);
        $this->assertFalse($data->staff_evidence_by_staff->can_modify());
        $this->assertFalse($data->staff_evidence_by_manager->can_modify());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->manager_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->manager_user->id)->can_create());

        unassign_capability('totara/evidence:viewanyevidenceonself', $role);
        assign_capability('totara/evidence:manageownevidenceonself', CAP_ALLOW, $role, $context);
        $this->assertTrue($data->staff_evidence_by_staff->can_modify());
        $this->assertFalse($data->staff_evidence_by_manager->can_modify());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->manager_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->manager_user->id)->can_create());

        unassign_capability('totara/evidence:manageownevidenceonself', $role);
        assign_capability('totara/evidence:manageanyevidenceonself', CAP_ALLOW, $role, $context);
        $this->assertTrue($data->staff_evidence_by_staff->can_modify());
        $this->assertTrue($data->staff_evidence_by_manager->can_modify());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->manager_evidence_by_manager)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->manager_user->id)->can_create());
    }

    /**
     * Test that the manage and view capabilities work as intended for Staff Managers
     */
    public function test_manager_capabilities(): void {
        $data = $this->create_test_data();
        self::setUser($data->manager_user->id);
        $role = builder::table('role')->where('shortname', 'staffmanager')->value('id');
        $context = context_user::instance($data->staff_user->id);

        unassign_capability('totara/evidence:viewanyevidenceonothers', $role);
        unassign_capability('totara/evidence:manageanyevidenceonothers', $role);
        unassign_capability('totara/evidence:manageownevidenceonothers', $role);
        $this->assertFalse($data->staff_evidence_by_staff->can_modify());
        $this->assertFalse($data->staff_evidence_by_manager->can_modify());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());

        assign_capability('totara/evidence:viewanyevidenceonothers', CAP_ALLOW, $role, $context);
        $this->assertFalse($data->staff_evidence_by_staff->can_modify());
        $this->assertFalse($data->staff_evidence_by_manager->can_modify());
        $this->assertFalse(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());

        unassign_capability('totara/evidence:viewanyevidenceonothers', $role);
        assign_capability('totara/evidence:manageownevidenceonothers', CAP_ALLOW, $role, $context);
        $this->assertFalse($data->staff_evidence_by_staff->can_modify());
        $this->assertTrue($data->staff_evidence_by_manager->can_modify());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertFalse(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());

        unassign_capability('totara/evidence:manageownevidenceonothers', $role);
        assign_capability('totara/evidence:manageanyevidenceonothers', CAP_ALLOW, $role, $context);
        $this->assertTrue($data->staff_evidence_by_staff->can_modify());
        $this->assertTrue($data->staff_evidence_by_manager->can_modify());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_create());
        $this->assertTrue(evidence_item_capability_helper::for_user($data->staff_user->id)->can_view_list());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_staff)->can_view_item());
        $this->assertTrue(evidence_item_capability_helper::for_item($data->staff_evidence_by_manager)->can_view_item());
    }

    public function test_can_view_own_items_only(): void {
        $data = $this->create_test_data();
        self::setUser($data->manager_user->id);
        $capability_helper = evidence_item_capability_helper::for_user($data->staff_user->id);
        $role = builder::table('role')->where('shortname', 'staffmanager')->value('id');
        $context = context_user::instance($data->staff_user->id);

        $this->assertFalse(evidence_item_capability_helper::for_user(user::logged_in()->id)->can_view_own_items_only());

        assign_capability('totara/evidence:viewanyevidenceonothers', CAP_ALLOW, $role, $context);
        $this->assertFalse($capability_helper->can_view_own_items_only());

        unassign_capability('totara/evidence:viewanyevidenceonothers', $role);
        assign_capability('totara/evidence:manageanyevidenceonothers', CAP_ALLOW, $role, $context);
        $this->assertFalse($capability_helper->can_view_own_items_only());

        unassign_capability('totara/evidence:manageanyevidenceonothers', $role);
        assign_capability('totara/evidence:manageownevidenceonothers', CAP_ALLOW, $role, $context);
        $this->assertTrue($capability_helper->can_view_own_items_only());

        unassign_capability('totara/evidence:manageownevidenceonothers', $role);
        $this->assertFalse($capability_helper->can_view_own_items_only());
    }

    public function test_cannot_modify_without_item_specified(): void {
        self::setAdminUser();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Please use evidence_capability_helper::for_item() instead of for_user()');
        evidence_item_capability_helper::for_user(user::logged_in()->id)->can_modify();
    }

    public function test_cannot_view_item_without_item_specified(): void {
        self::setAdminUser();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Please use evidence_capability_helper::for_item() instead of for_user()');
        evidence_item_capability_helper::for_user(user::logged_in()->id)->can_view_item();
    }

    public function test_specifying_required_throws_exceptions(): void {
        $data = $this->create_test_data();
        self::setUser($data->staff_user->id);
        $capability_helper = evidence_item_capability_helper::for_item($data->manager_evidence_by_manager);

        try {
            $capability_helper->can_create(true);
            self::fail('Expected exception was not thrown');
        } catch (required_capability_exception $exception) {
            $this->assertFalse($capability_helper->can_create());
        }
        try {
            $capability_helper->can_modify(true);
            self::fail('Expected exception was not thrown');
        } catch (required_capability_exception $exception) {
            $this->assertFalse($capability_helper->can_create());
        }
        try {
            $capability_helper->can_view_list(true);
            self::fail('Expected exception was not thrown');
        } catch (required_capability_exception $exception) {
            $this->assertFalse($capability_helper->can_view_list());
        }
        try {
            $capability_helper->can_view_item(true);
            self::fail('Expected exception was not thrown');
        } catch (required_capability_exception $exception) {
            $this->assertFalse($capability_helper->can_view_item());
        }
    }

    public function test_must_be_admin_to_manage_imported_evidence(): void {
        $data = $this->create_test_data();
        $admin_user = $this->generator()->create_evidence_user();
        $system_context = context_system::instance();
        $manager_role = builder::table('role')->where('shortname', 'staffmanager')->value('id');
        $user_role = builder::table('role')->where('shortname', 'user')->value('id');

        /** @var evidence_type $system_type */
        $system_type = evidence_type::repository()
            ->filter_by_system_location()
            ->order_by('id')
            ->first();
        $imported_evidence = $this->generator()->create_evidence_item([
            'user_id'    => $data->staff_user->id,
            'created_by' => $admin_user->id,
            'type'       => $system_type,
            'imported'   => 1,
        ]);
        $capability_helper = evidence_item_capability_helper::for_item($imported_evidence);

        // Manager can't edit their staff's system evidence until assigned manageanyevidenceonothers capability
        self::setUser($data->manager_user->id);
        $this->assertFalse($capability_helper->can_modify());
        assign_capability('totara/evidence:manageanyevidenceonothers', CAP_ALLOW, $manager_role, $system_context);
        $this->assertTrue($capability_helper->can_modify());

        // User can't edit their own system evidence until assigned manageanyevidenceonothers capability
        self::setUser($data->staff_user->id);
        $this->assertFalse($capability_helper->can_modify());
        assign_capability('totara/evidence:manageanyevidenceonothers', CAP_ALLOW, $user_role, $system_context);
        $this->assertTrue($capability_helper->can_modify());
    }

    private function create_test_data(): test_data {
        self::setAdminUser();
        return new test_data($this->generator());
    }
}

class test_data {
    /** @var user */
    public $manager_user;
    /** @var user */
    public $staff_user;
    /** @var context_user */
    public $user_context;
    /** @var evidence_item */
    public $staff_evidence_by_staff;
    /** @var evidence_item */
    public $staff_evidence_by_manager;
    /** @var evidence_item */
    public $manager_evidence_by_manager;

    public function __construct(\totara_evidence\testing\generator $generator) {
        $this->manager_user = $generator->create_evidence_user();
        $this->staff_user = $generator->create_evidence_user();
        $generator->create_relationship($this->staff_user->id, $this->manager_user->id);
        $this->user_context = context_user::instance($this->staff_user->id);

        $generator->create_evidence_type();
        $this->staff_evidence_by_staff = $generator->create_evidence_item([
            'user_id'    => $this->staff_user->id,
            'created_by' => $this->staff_user->id,
        ]);
        $this->staff_evidence_by_manager = $generator->create_evidence_item([
            'user_id'    => $this->staff_user->id,
            'created_by' => $this->manager_user->id,
        ]);
        $this->manager_evidence_by_manager = $generator->create_evidence_item([
            'user_id'    => $this->manager_user->id,
            'created_by' => $this->manager_user->id,
        ]);
    }
}
