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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

/**
 * @group perform
 */
class mod_perform_admin_menu_testcase extends advanced_testcase {

    public function test_admin_menu_contains_reporting_link() {
        $user = $this->getDataGenerator()->create_user();

        // The capability is added to the role in the system context.
        $sys_context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/perform:report_on_all_subjects_responses', CAP_ALLOW, $roleid, $sys_context);

        // The role is granted in the user's own context.
        $user_context = \context_user::instance($user->id);
        role_assign($roleid, $user->id, $user_context);

        self::assertInstanceOf(admin_externalpage::class, admin_get_root()->locate('mod_perform_activity_response_data'));
    }
}
