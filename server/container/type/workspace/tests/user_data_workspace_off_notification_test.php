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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\workspace;
use container_workspace\entity\workspace_off_notification;
use container_workspace\notification\workspace_notification;
use totara_userdata\userdata\target_user;
use container_workspace\userdata\workspace_off_notification as user_data_workspace_off_notification;

class container_workspace_user_data_workspace_off_notification_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_purge_off_notification_records(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        for ($i = 0; $i < 5; $i++) {
            $workspace = $workspace_generator->create_workspace();
            workspace_notification::off($workspace->get_id(), $user_one->id);
        }

        $this->assertEquals(
            5,
            $DB->count_records(workspace_off_notification::TABLE, ['user_id' => $user_one->id])
        );

        // Now purge the user.
        delete_user($user_one);
        $user_one = core_user::get_user($user_one->id);

        $target_user = new target_user($user_one);
        user_data_workspace_off_notification::execute_purge($target_user, context_system::instance());

        $this->assertEquals(
            0,
            $DB->count_records(workspace_off_notification::TABLE, ['user_id' => $user_one->id])
        );
    }

    /**
     * @return void
     */
    public function test_purge_off_notification_records_within_category(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $default_category_id = workspace::get_default_category_id();

        for ($i = 0; $i < 3; $i++) {
            $workspace = $workspace_generator->create_workspace();
            workspace_notification::off($workspace->get_id(), $user_one->id);
        }

        $this->assertEquals(
            3,
            $DB->count_records(workspace_off_notification::TABLE, ['user_id' => $user_one->id])
        );

        // Now purge the user.
        delete_user($user_one);
        $user_one = core_user::get_user($user_one->id);

        $target_user = new target_user($user_one);
        $context_course_category = context_coursecat::instance($default_category_id);

        user_data_workspace_off_notification::execute_purge($target_user, $context_course_category);

        $this->assertEquals(
            0,
            $DB->count_records(workspace_off_notification::TABLE, ['user_id' => $user_one->id])
        );
    }
}