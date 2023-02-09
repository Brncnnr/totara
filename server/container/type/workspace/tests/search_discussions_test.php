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
use container_workspace\discussion\discussion;
use container_workspace\query\discussion\query as discussion_query;
use container_workspace\loader\discussion\loader as discussion_loader;

class container_workspace_search_discussions_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_search_for_discussions_via_comments(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add a discussion for user one in the workspace.
        $workspace_id = $workspace->get_id();
        $discussion = $workspace_generator->create_discussion($workspace_id);

        // Add comments to the discussion.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA,
            "This is meant to be searched for",
            FORMAT_JSON_EDITOR
        );

        // Add a few more comments/discussions to the workspace.
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $workspace_generator->add_member($workspace, $user->id, $user_one->id);

            // Create discussion for such user.
            $user_discussion = $workspace_generator->create_discussion(
                $workspace_id,
                uniqid("user_{$user->id}"),
                null,
                FORMAT_JSON_EDITOR,
                $user->id
            );

            // Create comment for the user discussion.
            $comment_generator->create_comment(
                $user_discussion->get_id(),
                workspace::get_type(),
                discussion::AREA,
                uniqid("user_{$user->id}"),
                FORMAT_JSON_EDITOR,
                $user->id
            );
        }

        // Search for the discussion.
        $query = new discussion_query($workspace_id);
        $query->set_search_term('this');

        $cursor_paginator = discussion_loader::get_discussions($query);
        self::assertEquals(1, $cursor_paginator->get_total());

        $fetched_discussions = $cursor_paginator->get_items()->all();
        $fetched_discusison = reset($fetched_discussions);

        self::assertEquals($discussion->get_id(), $fetched_discusison->get_id());
    }

    /**
     * This is to make sure that we are not going to returned any duplicated id(s) from the discussion.
     * @return void
     */
    public function test_search_for_discussions_via_duplicated_comments(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Add multiple comments with the same content to the discussion.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        for ($i = 0; $i < 5; $i++) {
            $comment_generator->create_comment(
                $discussion->get_id(),
                workspace::get_type(),
                discussion::AREA,
                "This is the same comment",
                FORMAT_JSON_EDITOR
            );
        }

        // Search for the discussion via comments - which it should not result in duplicated discussions.
        $query = new discussion_query($workspace->get_id());
        $query->set_search_term("This is the same comment");

        $cursor_paginator = discussion_loader::get_discussions($query);
        self::assertEquals(1, $cursor_paginator->get_total());

        /** @var discussion[] $fetched_discussions */
        $fetched_discussions = $cursor_paginator->get_items()->all();
        self::assertCount(1, $fetched_discussions);

        $fetched_discussion = reset($fetched_discussions);
        self::assertEquals($discussion->get_id(), $fetched_discussion->get_id());
    }
}