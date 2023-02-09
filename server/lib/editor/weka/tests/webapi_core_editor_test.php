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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use editor_weka\extension\extension;
use editor_weka\factory\extension_loader;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\webapi\resolver\query\editor;
use core\editor\variant_name;

class editor_weka_webapi_core_editor_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_json_editor(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'format' => FORMAT_JSON_EDITOR,
                'context_id' => context_system::instance()->id
            ]
        );

        self::assertInstanceOf(weka_texteditor::class, $editor);
    }

    /**
     * @return void
     */
    public function test_get_json_editor_with_variant_standard(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context_user = context_user::instance($user_one->id);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_user->id,
                'format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);

        self::assertIsArray($result->data);
        self::assertArrayHasKey('editor', $result->data);
        self::assertIsArray($result->data['editor']);

        $editor_data = $result->data['editor'];

        self::assertArrayHasKey('name', $editor_data);
        self::assertEquals('weka', $editor_data['name']);

        self::assertArrayHasKey('context_id', $editor_data);
        self::assertEquals($context_user->id, $editor_data['context_id']);

        self::assertArrayHasKey('variant', $editor_data);
        self::assertIsArray($editor_data['variant']);
        self::assertArrayHasKey('options', $editor_data['variant']);
        self::assertArrayHasKey('name', $editor_data['variant']);

        self::assertEquals(variant_name::STANDARD, $editor_data['variant']['name']);
        self::assertNotEmpty($editor_data['variant']['options']);
        self::assertIsString($editor_data['variant']['options']);

        $options_json_blob = $editor_data['variant']['options'];
        $options = json_decode($options_json_blob, true);

        self::assertEquals(JSON_ERROR_NONE, json_last_error());
        self::assertIsArray($options);
        self::assertNotEmpty($options);

        self::assertArrayHasKey('extensions', $options);
        ['extensions' => $all_extensions] = extension_loader::get_extensions_for_variant(variant_name::STANDARD);
        self::assertCount(count($all_extensions), $options['extensions']);

        $test_case = $this;
        $extension_names = array_map(
            function (array $extension_metadata) use ($test_case) : string {
                $test_case::assertArrayHasKey('name', $extension_metadata);
                return $extension_metadata['name'];
            },
            $options['extensions']
        );

        foreach ($all_extensions as $extension_class) {
            /** @see extension::get_extension_name() */
            $extension_name = call_user_func([$extension_class, 'get_extension_name']);
            self::assertContainsEquals($extension_name, $extension_names);
        }
    }
}