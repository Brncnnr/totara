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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\editor\variant_name;
use core\webapi\resolver\query\editor;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_tui\output\framework;

class core_webapi_get_editor_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_non_supported_variant_editor(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_user->id,
                'format' => FORMAT_PLAIN
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);

        self::assertArrayHasKey('editor', $result->data);
        self::assertNotEmpty($result->data['editor']);

        $editor_data = $result->data['editor'];
        self::assertArrayHasKey('name', $editor_data);
        self::assertEquals('textarea', $editor_data['name']);

        self::assertArrayHasKey('context_id', $editor_data);
        self::assertEquals($context_user->id, $editor_data['context_id']);

        self::assertArrayHasKey('variant', $editor_data);
        self::assertIsArray($editor_data['variant']);
        self::assertArrayHasKey('options', $editor_data['variant']);
        self::assertArrayHasKey('name', $editor_data['variant']);

        self::assertEquals(variant_name::STANDARD, $editor_data['variant']['name']);
        self::assertNull($editor_data['variant']['options']);
    }

    /**
     * @return void
     */
    public function test_get_non_supported_variant_editor_with_variant_standard_no_description(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_user->id,
                'format' => FORMAT_PLAIN,
                'variant_name' => variant_name::DESCRIPTION
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('editor', $result->data);

        $editor_data = $result->data['editor'];
        self::assertArrayHasKey('name', $editor_data);
        self::assertEquals('textarea', $editor_data['name']);

        self::assertArrayHasKey('context_id', $editor_data);
        self::assertEquals($context_user->id, $editor_data['context_id']);

        self::assertArrayHasKey('variant', $editor_data);
        self::assertIsArray($editor_data['variant']);
        self::assertArrayHasKey('options', $editor_data['variant']);
        self::assertArrayHasKey('name', $editor_data['variant']);

        self::assertEquals(variant_name::DESCRIPTION, $editor_data['variant']['name']);
        self::assertNull($editor_data['variant']['options']);
    }

    /**
     * @return void
     */
    public function test_get_non_supported_variant_editor_with_invalid_variant_name(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'format' => FORMAT_PLAIN,
                'variant_name' => 'hello_world_bolobala'
            ]
        );

        self::assertNotEmpty($result->errors);
        self::assertEmpty($result->data);

        self::assertCount(1, $result->errors);
        $error = reset($result->errors);

        self::assertStringContainsString("Invalid variant name 'hello_world_bolobala'", $error->getMessage());
    }

    /**
     * @return void
     */
    public function test_get_editor_with_context_id_as_system_should_not_yield_error(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        try {
            $editor = $this->resolve_graphql_query(
                $this->get_graphql_name(editor::class),
                [
                    'context_id' => context_system::instance()->id,
                    'format' => FORMAT_PLAIN
                ]
            );

            self::assertInstanceOf(texteditor::class, $editor);
        } catch (coding_exception $e) {
            $this->fail("Expecting the context_id as system context should not yield any error");
        }
    }

    /**
     * @return void
     */
    public function test_get_editor_with_framework_incompatible(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context_user = context_user::instance($user_one->id);

        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'context_id' =>  $context_user->id,
                'format' => FORMAT_HTML,
                'framework' => framework::COMPONENT
            ]
        );

        self::assertNotInstanceOf(weka_texteditor::class, $editor);
        self::assertNotInstanceOf(atto_texteditor::class, $editor);
        self::assertInstanceOf(textarea_texteditor::class, $editor);
    }

    /**
     * @return void
     */
    public function test_get_weka_editor_with_framework_compatible(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context_user = context_user::instance($user_one->id);

        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'context_id' =>  $context_user->id,
                'format' => FORMAT_JSON_EDITOR,
                'framework' => framework::COMPONENT
            ]
        );

        self::assertNotInstanceOf(textarea_texteditor::class, $editor);
        self::assertNotInstanceOf(atto_texteditor::class, $editor);
        self::assertInstanceOf(weka_texteditor::class, $editor);
    }
}