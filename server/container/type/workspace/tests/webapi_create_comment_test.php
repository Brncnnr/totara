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

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\discussion\discussion;
use container_workspace\workspace;
use totara_comment\exception\comment_exception;
use totara_comment\comment;
use core\json_editor\node\paragraph;

class container_workspace_webapi_create_comment_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_comment_on_a_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Flag the workspace to be deleted.
        $workspace->mark_to_be_deleted(true);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:create', 'totara_comment'));

        $this->resolve_graphql_mutation(
            'totara_comment_create_comment',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('woo')]
                ]),
                'format' => FORMAT_JSON_EDITOR
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_reply_on_a_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        // Create a comment first - so that we can create a reply afterward.
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Flag the workspace to be deleted - then run.
        $workspace->mark_to_be_deleted();

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:create', 'totara_comment'));

        $this->resolve_graphql_mutation(
            'totara_comment_create_reply',
            [
                'commentid' => $comment->get_id(),
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('wow')]
                ]),
                'format' => FORMAT_JSON_EDITOR
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_a_comment_of_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion, so that we can create a comment.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Flag the workspace to be deleted - then check that we are able to update the comment at all.
        // The result should allow us to update the comment - event though the workspace has been deleted.
        $workspace->mark_to_be_deleted();
        $content =  json_encode([
            'type' => 'doc',
            'content' => [paragraph::create_json_node_from_text('new content')],
        ]);
        /** @var comment $updated_comment */
        $updated_comment = $this->resolve_graphql_mutation(
            'totara_comment_update_comment',
            [
                'id' => $comment->get_id(),
                'content' => $content,
                'format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertInstanceOf(comment::class, $updated_comment);
        self::assertEquals($comment->get_id(), $updated_comment->get_id());

        self::assertEquals($content, $updated_comment->get_content());
        self::assertEquals(FORMAT_JSON_EDITOR, $updated_comment->get_format());
    }

    /**
     * @return void
     */
    public function test_update_a_reply_of_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion, then a comment and a reply.=
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        $reply = $comment_generator->create_reply($comment->get_id());

        // Flag the workspace to be deleted - so that we can run the mutation to check if it
        // allows us to update the reply. As a result, it should allow us to update the reply.
        $workspace->mark_to_be_deleted();
        $content = json_encode([
            'type' => 'doc',
            'content' => [paragraph::create_json_node_from_text('wow')]
        ]);

        /** @var comment $updated_reply */
        $updated_reply = $this->resolve_graphql_mutation(
            'totara_comment_update_reply',
            [
                'id' => $reply->get_id(),
                'content' => $content,
                'format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertInstanceOf(comment::class, $updated_reply);
        self::assertTrue($updated_reply->is_reply());

        self::assertEquals($reply->get_id(), $updated_reply->get_id());
        self::assertEquals($content, $updated_reply->get_content());
        self::assertEquals(FORMAT_JSON_EDITOR, $updated_reply->get_format());
    }
}