<?php
/*
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use core\orm\query\builder;
use totara_competency\helpers\capability_helper;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_capability_helper_testcase extends advanced_testcase {

    public function can_view_and_assign_self_data_provider() {
        return [
            ['can_view_profile', 'totara/competency:view_own_profile', 'View own competency profile'],
            ['can_assign', 'totara/competency:assign_self', 'Assign competency to yourself'],
        ];
    }

    /**
     * Make sure capability checks for viewing own competency profile and assigning competency to oneself work as
     * expected. The logic for both is the same.
     *
     * @dataProvider can_view_and_assign_self_data_provider
     *
     * @param string $method
     * @param string $capability
     * @param string $exception_message
     */
    public function test_can_view_profile_and_assign_self(string $method, string $capability, string $exception_message) {
        $role = builder::table('role')->where('shortname', 'user')->one()->id;

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $self_context = context_user::instance($user1->id);
        $system_context = context_system::instance();

        // Bad user id returns false.
        $this->assertFalse(capability_helper::$method(null));

        // No capability: Can't see or assign anything.
        unassign_capability($capability, $role);
        $this->assertFalse(capability_helper::$method($user1->id));
        $this->assertFalse(capability_helper::$method($user1->id, $self_context));

        // With capability.
        assign_capability($capability, CAP_ALLOW, $role, $self_context->id);
        $this->assertTrue(capability_helper::$method($user1->id));
        $this->assertTrue(capability_helper::$method($user1->id, $self_context));
        // Context where capability is not valid.
        $this->assertFalse(capability_helper::$method($user1->id, $system_context));

        // require wrapper should throw exception.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage($exception_message);
        $require_method = 'require_' . $method;
        capability_helper::$require_method($user1->id, $system_context);
    }

    public function can_view_and_assign_other_data_provider() {
        return [
            ['can_view_profile', 'totara/competency:view_other_profile', 'View profile of other users'],
            ['can_assign', 'totara/competency:assign_other', 'Assign competency to other users'],
        ];
    }

    /**
     * Make sure capability checks for viewing other's competency profile and assigning competency to other users
     * work as expected. The logic for both is the same.
     *
     * @dataProvider can_view_and_assign_other_data_provider
     *
     * @param string $method
     * @param string $capability
     * @param string $exception_message
     */
    public function test_can_view_profile_and_assign_other(string $method, string $capability, string $exception_message) {
        $role = builder::table('role')->where('shortname', 'user')->one()->id;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $user2_context = context_user::instance($user2->id);
        $system_context = context_system::instance();

        // No capability: Can't see anything.
        unassign_capability($capability, $role);
        $this->assertFalse(capability_helper::$method($user2->id));

        // With capability to view other.
        assign_capability($capability, CAP_ALLOW, $role, $user2_context->id);
        $this->assertTrue(capability_helper::$method($user2->id));
        $this->assertTrue(capability_helper::$method($user2->id, $user2_context));
        // Context where capability is not valid.
        $this->assertFalse(capability_helper::$method($user2->id, $system_context));

        // require wrapper should throw exception.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage($exception_message);
        $require_method = 'require_' . $method;
        capability_helper::$require_method($user2->id, $system_context);
    }

    /**
     * Check that as an appraiser, competency profile can be viewed and competencies can be assigned without needing
     * any capabilities.
     *
     * @dataProvider can_view_and_assign_other_data_provider
     *
     * @param string $method
     * @param string $capability
     * @param string $exception_message
     */
    public function test_appraiser_can_view_profile_and_assign_other(
        string $method,
        string $capability,
        string $exception_message
    ) {
        $role = builder::table('role')->where('shortname', 'user')->one()->id;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        job_assignment::create(['userid' => $user2->id, 'appraiserid' => $user1->id, 'idnumber' => 2]);

        $user2_context = context_user::instance($user2->id);
        $system_context = context_system::instance();

        // Appraiser has permission for this without capability.
        unassign_capability($capability, $role);
        $this->assertTrue(capability_helper::$method($user2->id));
        // Context doesn't matter for an appraiser.
        $this->assertTrue(capability_helper::$method($user2->id, $user2_context));
        $this->assertTrue(capability_helper::$method($user2->id, $system_context));

        // require wrapper doesn't throw exception.
        $require_method = 'require_' . $method;
        capability_helper::$require_method($user2->id);
    }

    /**
     * Test whether can_view_profile does still work even if user got deleted
     */
    public function test_can_view_profile_for_deleted_users() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $managerja = job_assignment::create(['userid' => $user1->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $user2->id, 'managerjaid' => $managerja->id, 'idnumber' => 2]);

        $this->setUser($user1);

        $can_view = capability_helper::can_view_profile($user2->id);
        $this->assertTrue($can_view);

        $this->setAdminUser();

        delete_user($user2);

        $this->setUser($user1);

        $can_view = capability_helper::can_view_profile($user2->id);
        $this->assertFalse($can_view);
    }

}