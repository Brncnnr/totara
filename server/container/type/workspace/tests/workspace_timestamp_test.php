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

use container_workspace\discussion\discussion;
use container_workspace\discussion\discussion_helper;
use container_workspace\local\workspace_helper;
use container_workspace\tracker\tracker;
use container_workspace\workspace;
use core\json_editor\node\paragraph;
use totara_comment\comment_helper;
use container_workspace\totara_engage\share\recipient\library;
use container_workspace\member\member;
use totara_engage\access\access;
use totara_engage\share\manager;

class container_workspace_workspace_timestamp_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_timestamp_does_update_tracker(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace, time());

        workspace_helper::update_workspace_timestamp($workspace, $user_one->id, 100);

        $workspace_timestamp = $workspace->get_timestamp();
        $last_time_visit = $tracker->get_last_time_visit_workspace($workspace->get_id());

        $this->assertEquals($workspace_timestamp, $last_time_visit);
        $this->assertEquals(100, $workspace_timestamp);
        $this->assertEquals(100, $last_time_visit);
    }

    /**
     * @return void
     */
    public function test_create_discussion_update_timestamp(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $original_timestamp = $workspace->get_timestamp();

        // Wait for a second before adding a discussion to the workspace.
        $this->waitForSecond();
        discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text("This is a discussion")
                ]
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        $updated_timestamp = $workspace->get_timestamp();
        $this->assertLessThan($updated_timestamp, $original_timestamp);
    }

    /**
     * @return void
     */
    public function test_add_comment_to_discussion_update_timestamp(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $original_timestamp = $workspace->get_timestamp();

        $this->waitForSecond();
        $discussion = discussion_helper::create_discussion($workspace, "Wohoo this is the discussion");

        // Add comment to the workspace.
        $this->waitForSecond();
        comment_helper::create_comment(
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id(),
            'This is the comment'
        );

        $updated_timestamp = $workspace->get_timestamp();
        $this->assertLessThan($updated_timestamp, $original_timestamp);
    }

    /**
     * @return void
     */
    public function test_add_reply_to_discussion_comment_update_timestamp(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $workspace->get_id();
        $discussion = $workspace_generator->create_discussion($workspace_id);

        // Log in as second user - join the workspace and create comment and reply to the discussion.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        $workspace->reload();
        $before_reply_timestamp = $workspace->get_timestamp();

        // Create a reply after a second.
        $this->waitForSecond();
        comment_helper::create_reply($comment->get_id(), "Di di du du");

        $workspace->reload();
        $updated_timestamp = $workspace->get_timestamp();

        $this->assertLessThan($updated_timestamp, $before_reply_timestamp);
    }


    /**
     * @return void
     */
    public function test_share_resources_update_timestamp(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::PUBLIC]);

        $original_timestamp = $workspace->get_timestamp();

        // Wait for the next second then send the share.
        $this->waitForSecond();
        $recipient = new library($workspace->get_id());
        manager::share($article, 'engage_article', [$recipient]);
        $article_generator->share_article($article, [$recipient]);

        $workspace->reload();
        $timestamp = $workspace->get_timestamp();

        $this->assertLessThan($timestamp, $original_timestamp);
    }
}