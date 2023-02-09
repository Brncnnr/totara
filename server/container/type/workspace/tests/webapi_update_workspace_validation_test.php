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
use container_workspace\workspace;

class container_workspace_update_workspace_validation_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_update_workspace_with_different_description_format_from_json_editor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'description' => 'Hello world',
                'description_format' => FORMAT_PLAIN
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_workspace_with_empty_description_document_should_result_in_empty_string(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var workspace $updated_workspace */
        $updated_workspace = $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'description' => json_encode([
                    'type' => 'doc',
                    'content' => []
                ]),
                'description_format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertInstanceOf(workspace::class, $updated_workspace);
        self::assertEquals($workspace->get_id(), $updated_workspace->get_id());

        self::assertEmpty($updated_workspace->summary);
        self::assertNotNull($updated_workspace->summary);
    }
}