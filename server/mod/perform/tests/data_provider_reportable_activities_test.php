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
* @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
* @package mod_perform
*/
/**
 * @group perform
 */

use  mod_perform\data_providers\activity\reportable_activities;

class mod_perform_data_provider_reportable_activities_testcase extends advanced_testcase {

    public function test_fetch_for_normal_user() {
        $this->create_test_data();
        $normal_user = self::getDataGenerator()->create_user();
        self::setUser($normal_user);

        $data_provider = new reportable_activities();
        $activities = $data_provider->fetch()->get();

        $this->assertEmpty($activities);
    }

    public function test_fetch_for_report_admin() {
        $data = $this->create_test_data();
        $report_admin = self::getDataGenerator()->create_user();

        // The capability is added to the role in the system context.
        $sys_context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/perform:report_on_all_subjects_responses', CAP_ALLOW, $roleid, $sys_context);

        // The role is granted in the user's own context.
        $user_context = \context_user::instance($report_admin->id);
        role_assign($roleid, $report_admin->id, $user_context);

        self::setUser($report_admin);

        $data_provider = new reportable_activities();
        $activities = $data_provider->fetch()->get();

        $this->assertCount(2, $activities);
        $this->assertEqualsCanonicalizing(
            [$data->activity1->name, $data->activity2->name],
            [$activities->first()->name, $activities->last()->name]
        );
    }

    private function create_test_data(): stdClass {
        self::setAdminUser();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();

        $data->activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'Mid year performance']);
        $data->activity2 = $perform_generator->create_activity_in_container(['activity_name' => 'End year performance']);

        return $data;
    }
}