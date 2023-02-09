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
use container_workspace\exception\discussion_exception;
use core\json_editor\node\paragraph;

class container_workspace_webapi_create_discussion_validation_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_discussion_with_different_format(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        $this->resolve_graphql_mutation(
            'container_workspace_create_discussion',
            [
                'workspace_id' => $workspace->get_id(),
                'content' => 'BOOM !!',
                'content_format' => FORMAT_PLAIN
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_discussion_with_empty_content(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $this->expectException(discussion_exception::class);
        $this->expectExceptionMessage(get_string('error:create_discussion', 'container_workspace'));

        $this->resolve_graphql_mutation(
            'container_workspace_create_discussion',
            [
                'workspace_id' => $workspace->get_id(),
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => []
                ]),
                'content_format' => FORMAT_JSON_EDITOR
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_discussion_with_content_that_has_empty_paragraph_node(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $this->expectException(discussion_exception::class);
        $this->expectExceptionMessage(get_string('error:create_discussion', 'container_workspace'));

        $this->resolve_graphql_mutation(
            'container_workspace_create_discussion',
            [
                'workspace_id' => $workspace->get_id(),
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
}