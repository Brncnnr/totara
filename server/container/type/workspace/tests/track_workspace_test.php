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

use container_workspace\member\member;
use container_workspace\tracker\tracker;
use core\entity\user_last_access;
use container_workspace\discussion\discussion_helper;

class container_workspace_track_workspace_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_workspace_timestamp_when_create_a_discussion(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user one to this workspace.
        $user_one = $generator->create_user();
        member::added_to_workspace($workspace, $user_one->id, false);

        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace, 50);

        $time_visited_50 = $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        self::assertEquals(50, $time_visited_50);

        // Unset the user in session and try to create the discussion as user_one - see if the tracker is updated.
        $this->setUser(null);
        discussion_helper::create_discussion(
            $workspace,
            'content',
            null,
            FORMAT_PLAIN,
            $user_one->id
        );

        $time_visited_recently =  $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        self::assertNotEquals($time_visited_50, $time_visited_recently);
    }

    /**
     * @return void
     */
    public function test_update_workspace_timestamp_when_create_a_discussion_with_guest_user_in_session(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user one to this workspace.
        $user_one = $generator->create_user();
        member::added_to_workspace($workspace, $user_one->id, false);

        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace, 50);

        $time_visited_50 = $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        self::assertEquals(50, $time_visited_50);

        // Set the user as guest user so that we can check if we are able to pass thru the $actor_id
        // all the way down to the observer.
        $this->setGuestUser();

        discussion_helper::create_discussion(
            $workspace,
            'content',
            null,
            FORMAT_PLAIN,
            $user_one->id
        );

        $time_visited_recently =  $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        self::assertNotEquals($time_visited_50, $time_visited_recently);
    }

    /**
     * @return void
     */
    public function test_update_workspace_timestamp_when_update_a_discussion(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user one to this workspace.
        $user_one = $generator->create_user();
        member::added_to_workspace($workspace, $user_one->id, false);

        // Unset the user in session and try to create the discussion as user_one - see if the tracker is updated.
        $this->setUser(null);
        $discussion = discussion_helper::create_discussion(
            $workspace,
            'content',
            null,
            FORMAT_PLAIN,
            $user_one->id
        );

        $time_visited_created =  $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        $this->waitForSecond();


        // Update the discussion.
        discussion_helper::update_discussion_content(
            $discussion->get_id(),
            'woho',
            null,
            FORMAT_PLAIN,
            $user_one->id
        );

        $time_visited_updated =  $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        self::assertNotEquals($time_visited_created, $time_visited_updated);
        self::assertGreaterThan($time_visited_created, $time_visited_updated);
    }


    /**
     * @return void
     */
    public function test_update_workspace_timestamp_when_update_a_discussion_with_guest_user_in_session(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user one to this workspace.
        $user_one = $generator->create_user();
        member::added_to_workspace($workspace, $user_one->id, false);

        // Unset the user in session and try to create the discussion as user_one - see if the tracker is updated.
        $this->setGuestUser();

        $discussion = discussion_helper::create_discussion(
            $workspace,
            'content',
            null,
            FORMAT_PLAIN,
            $user_one->id
        );

        $time_visited_created =  $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        self::assertNotEmpty($time_visited_created);
        $this->waitForSecond();

        // Update the discussion.
        discussion_helper::update_discussion_content(
            $discussion->get_id(),
            'woho',
            null,
            FORMAT_PLAIN,
            $user_one->id
        );

        $time_visited_updated =  $DB->get_field(
            user_last_access::TABLE,
            'timeaccess',
            [
                'courseid' => $workspace->get_id(),
                'userid' => $user_one->id
            ]
        );

        self::assertNotEquals($time_visited_created, $time_visited_updated);
        self::assertGreaterThan($time_visited_created, $time_visited_updated);
    }
}