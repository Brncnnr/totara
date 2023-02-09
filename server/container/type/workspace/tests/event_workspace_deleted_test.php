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

use container_workspace\event\workspace_deleted;

class container_workspace_event_workspace_deleted_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_instance(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $event = workspace_deleted::from_workspace($workspace);

        // This test seems to be redundant - but we got a case where factory of the class does not return the instance
        // of that class but different class. This is just to be sure nobody change it.
        self::assertInstanceOf(workspace_deleted::class, $event);
    }

    /**
     * @return void
     */
    public function test_create_instance_respect_actor_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_two = $generator->create_user();
        $event = workspace_deleted::from_workspace($workspace, $user_two->id);

        self::assertNotEquals($user_one->id, $event->userid, "Expecting that event respect the user argument");
        self::assertEquals($user_two->id, $event->userid, "Expecting that event respect the user argument");

        self::assertEquals(
            $user_one->id,
            $event->relateduserid,
            "Expecting that the event can figure out the target user"
        );
    }
}