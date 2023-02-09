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

use container_workspace\exception\discussion_exception;
use container_workspace\member\member;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\json_editor\node\paragraph;

class container_workspace_webapi_update_discussion_validation_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_update_discussion_with_different_format(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        $this->execute_mutation(
            [
                'id' => $discussion->get_id(),
                'content' => 'wooo',
                'content_format' => FORMAT_PLAIN
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_discussion_with_empty_discussion_content(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion(
            $workspace->get_id(),
            null,
            null,
            FORMAT_JSON_EDITOR
        );

        $this->expectException(discussion_exception::class);
        $this->expectExceptionMessage(get_string('error:update_discussion', 'container_workspace'));

        $this->execute_mutation(
            [
                'id' => $discussion->get_id(),
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => []
                ])
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_discussion_with_discussion_content_that_contains_empty_paragraph_node(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $this->expectException(discussion_exception::class);
        $this->expectExceptionMessage(get_string('error:update_discussion', 'container_workspace'));

        $this->execute_mutation(
            [
                'id' => $discussion->get_id(),
                'content_format' => FORMAT_JSON_EDITOR,
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [
                        [
                            'type' => paragraph::get_type(),
                            'content' => []
                        ]
                    ]
                ])
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_discussion_by_member_of_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $discussion = $workspace_generator->create_discussion($workspace->get_id());
        member::added_to_workspace($workspace, $user_two->id);

        $this->expectException(discussion_exception::class);
        $this->expectExceptionMessage(get_string('error:update_discussion', 'container_workspace'));

        $this->setUser($user_two);
        $this->execute_mutation(
            [
                'id' => $discussion->get_id(),
                'content_format' => FORMAT_JSON_EDITOR,
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('xxxooo')]
                ])
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_discussion_by_admin(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $this->setAdminUser();
        $result = $this->execute_mutation(
            [
                'id' => $discussion->get_id(),
                'content_format' => FORMAT_JSON_EDITOR,
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('new content')]
                ])
            ]
        );

        $this->assertNotEmpty($result);
        self::assertEquals('new content', $result->get_content_text());
    }

    /**
     * @return void
     */
    public function test_update_discussion_by_discussion_owner(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        member::added_to_workspace($workspace, $user_two->id);
        $discussion = $workspace_generator->create_discussion(
            $workspace->get_id(),
            null,
            null,
            null,
            $user_two->id
        );

        $result = $this->execute_mutation(
            [
                'id' => $discussion->get_id(),
                'content_format' => FORMAT_JSON_EDITOR,
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('new content')]
                ])
            ]
        );

        $this->assertNotEmpty($result);
        self::assertEquals('new content', $result->get_content_text());
    }

    private function execute_mutation(array $args) {
        return $this->resolve_graphql_mutation('container_workspace_update_discussion', $args);
    }

}