<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

use container_workspace\notification\workspace_notification;
use container_workspace\entity\workspace_off_notification;

class container_workspace_workspace_notification_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_turn_off_notification(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        // Create workspace for this user.
        /** @var \container_workspace\testing\generator $worksapce_generator */
        $worksapce_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $worksapce_generator->create_workspace();

        // Turn off notification for this specific workspace.
        $workspace_id = $workspace->get_id();
        workspace_notification::off($workspace_id, $user_one->id);

        $this->assertTrue(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_turn_on_notification(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $workspace->get_id();

        workspace_notification::off($workspace_id, $user_one->id);
        $this->assertTrue(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );

        // Turning on notification should delete the record.
        workspace_notification::on($workspace_id, $user_one->id);
        $this->assertFalse(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_turn_off_workspace_notification_twice_do_not_create_new_record(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Turn off the notification.
        $workspace_id = $workspace->get_id();
        workspace_notification::off($workspace_id, $user_one->id);

        $this->assertTrue(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );

        $this->assertEquals(
            1,
            $DB->count_records(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );

        workspace_notification::off($workspace_id, $user_one->id);

        $this->assertTrue(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );

        $this->assertEquals(
            1,
            $DB->count_records(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_turn_off_notification_as_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_two = $generator->create_user();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Only member of a workspace can turn off the notification");

        workspace_notification::off($workspace->get_id(), $user_two->id);
    }

    public function test_turn_on_notification_as_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_two = $generator->create_user();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Only member of a workspace can turn on the notification");

        workspace_notification::on($workspace->get_id(), $user_two->id);
    }
}