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

class container_workspace_webapi_create_workspace_validation_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_workspace_with_different_description_format_from_json_editor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        $this->resolve_graphql_mutation(
            'container_workspace_create',
            [
                'name' => 'admin',
                'description' => 'Hello world',
                'description_format' => FORMAT_PLAIN,
                'private' => false
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_workspace_empty_format_in_description(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var workspace $workspace */
        $workspace = $this->resolve_graphql_mutation(
            'container_workspace_create',
            [
                'name' => 'Hello world',
                'private' => false,
                'hidden' => false
            ]
        );

        self::assertInstanceOf(workspace::class, $workspace);
        self::assertNotEmpty($workspace->get_id());

        self::assertEquals('Hello world', $workspace->get_name());
        self::assertEmpty($workspace->summary);
        self::assertEquals(FORMAT_JSON_EDITOR, $workspace->summaryformat);
    }

    /**
     * @return void
     */
    public function test_create_workspace_with_empty_document_for_description(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var workspace $workspace */
        $workspace = $this->resolve_graphql_mutation(
            'container_workspace_create',
            [
                'name' => 'empty_name',
                'private' => false,
                'hidden' => false,
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => []
                ])
            ]
        );

        self::assertInstanceOf(workspace::class, $workspace);
        self::assertEmpty($workspace->summary);
        self::assertEquals(FORMAT_JSON_EDITOR, $workspace->summaryformat);
    }
}