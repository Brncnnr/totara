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

use editor_weka\factory\extension_loader;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * This test suite is to make sure that we still keep the support for the old stuff with weka.
 */
class editor_weka_webapi_core_editor_with_old_variant_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_editor_with_variant_from_engage_article(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_user->id,
                'format' => FORMAT_JSON_EDITOR,
                'variant_name' => 'full'
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('editor', $result->data);

        $editor_data = $result->data['editor'];
        self::assertIsArray($editor_data);
        self::assertArrayHasKey('name', $editor_data);
        self::assertEquals('weka', $editor_data['name']);

        self::assertArrayHasKey('context_id', $editor_data);
        self::assertEquals($context_user->id, $editor_data['context_id']);

        self::assertArrayHasKey('variant', $editor_data);

        $variant_data = $editor_data['variant'];
        self::assertIsArray($variant_data);
        self::assertArrayHasKey('name', $variant_data);
        self::assertEquals('full', $variant_data['name']);

        self::assertArrayHasKey('options', $variant_data);
        self::assertNotEmpty($variant_data['options']);
        self::assertIsString($variant_data['options']);

        $variant_options = json_decode($variant_data['options'], true);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());

        self::assertArrayHasKey('extensions', $variant_options);

        ['extensions' => $all_extensions] = extension_loader::get_extensions_for_variant('full');
        self::assertCount(count($all_extensions), $variant_options['extensions']);

        $test_case = $this;
        $extension_names = array_map(
            function (array $extension_metadata) use ($test_case) : string {
                $test_case::assertArrayHasKey('name', $extension_metadata);
                return $extension_metadata['name'];
            },
            $variant_options['extensions']
        );

        foreach ($all_extensions as $extension_class) {
            /** @see extension::get_extension_name() */
            $extension_name = call_user_func([$extension_class, 'get_extension_name']);
            self::assertContainsEquals($extension_name, $extension_names);
        }
    }

    /**
     * @return void
     */
    public function test_fetch_editor_with_variant_from_engage_article_and_usage_identifier(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_user->id,
                'format' => FORMAT_JSON_EDITOR,
                'variant_name' => 'full',
                'usage_identifier' => [
                    'component' => 'engage_article',
                    'area' => 'content',
                    'instance_id' => 42
                ]
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('editor', $result->data);

        $editor_data = $result->data['editor'];
        self::assertIsArray($editor_data);
        self::assertArrayHasKey('name', $editor_data);
        self::assertEquals('weka', $editor_data['name']);

        self::assertArrayHasKey('context_id', $editor_data);
        self::assertEquals($context_user->id, $editor_data['context_id']);

        self::assertArrayHasKey('variant', $editor_data);

        $variant_data = $editor_data['variant'];
        self::assertIsArray($variant_data);
        self::assertArrayHasKey('name', $variant_data);
        self::assertEquals('full', $variant_data['name']);

        self::assertArrayHasKey('options', $variant_data);
        self::assertNotEmpty($variant_data['options']);
        self::assertIsString($variant_data['options']);

        $variant_options = json_decode($variant_data['options'], true);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());

        self::assertArrayHasKey('extensions', $variant_options);
        ['extensions' => $all_extensions] = extension_loader::get_extensions_for_variant('full');
        self::assertCount(count($all_extensions), $variant_options['extensions']);

        $test_case = $this;
        $extension_names = array_map(
            function (array $extension_metadata) use ($test_case) : string {
                $test_case::assertArrayHasKey('name', $extension_metadata);
                return $extension_metadata['name'];
            },
            $variant_options['extensions']
        );

        foreach ($all_extensions as $extension_class) {
            /** @see extension::get_extension_name() */
            $extension_name = call_user_func([$extension_class, 'get_extension_name']);
            self::assertContainsEquals($extension_name, $extension_names);
        }
    }
}