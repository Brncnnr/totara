<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package weka_simple_multi_lang
 */

use core\editor\variant_name;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\orm\query\builder;
use weka_simple_multi_lang\extension;

class weka_simple_multi_lang_webapi_get_editor_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filterlib.php");
    }

    /**
     * @return void
     */
    public function test_get_extension_with_status_active_as_false_due_to_multi_lang_not_available(): void {
        $context_system = context_system::instance();

        $this->setAdminUser();
        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_system->id,
                'variant_name' => variant_name::SIMPLE,
                'format' => FORMAT_JSON_EDITOR,
                'framework' => 'tui',
                'extra_extensions' => json_encode([
                    [
                        'name' => extension::get_extension_name(),
                        'options' => [
                            'context_id' => $context_system->id,
                        ]
                    ]
                ])
            ]
        );

        self::assertEmpty($result->errors);
        self::assertIsArray($result->data);
        self::assertNotEmpty($result->data);

        self::assertArrayHasKey('editor', $result->data);

        $editor = $result->data['editor'];
        self::assertIsArray($editor);
        self::assertArrayHasKey('variant', $editor);

        $variant = $editor['variant'];
        self::assertIsArray($variant);
        self::assertNotEmpty($variant);

        self::assertArrayHasKey('options', $variant);
        self::assertIsString($variant['options']);

        $options = json_decode($variant['options'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($options);
        self::assertNotEmpty($options);

        self::assertArrayHasKey('extensions', $options);
        self::assertIsArray($options['extensions']);

        $extensions = array_filter(
            $options['extensions'],
            function (array $extension): bool {
                return $extension['name'] === extension::get_extension_name();
            }
        );

        self::assertCount(1, $extensions);
        $multi_lang_ext = reset($extensions);

        self::assertIsArray($multi_lang_ext);
        self::assertArrayHasKey('options', $multi_lang_ext);

        self::assertIsArray($multi_lang_ext['options']);
        self::assertArrayHasKey('is_active', $multi_lang_ext['options']);
        self::assertFalse($multi_lang_ext['options']['is_active']);
    }

    /**
     * @return void
     */
    public function test_get_extension_with_status_active_as_false_due_to_multi_lang_not_available_in_lower_context(): void {
        // Enable filter at context system.
        $context_system = context_system::instance();

        $db = builder::get_db();
        $record = new stdClass();
        $record->filter = 'multilang';
        $record->contextid = $context_system->id;
        $record->active = TEXTFILTER_OFF;

        $db->insert_record('filter_active', $record);
        $this->setAdminUser();

        // Create a course so that we can have a course context
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $context_course = context_course::instance($course->id);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_course->id,
                'variant_name' => variant_name::SIMPLE,
                'format' => FORMAT_JSON_EDITOR,
                'framework' => 'tui',
                'extra_extensions' => json_encode([
                    [
                        'name' => extension::get_extension_name(),
                        'options' => [
                            'context_id' => $context_course->id,
                        ]
                    ]
                ])
            ]
        );

        self::assertEmpty($result->errors);
        self::assertIsArray($result->data);
        self::assertNotEmpty($result->data);

        self::assertArrayHasKey('editor', $result->data);

        $editor = $result->data['editor'];
        self::assertIsArray($editor);
        self::assertArrayHasKey('variant', $editor);

        $variant = $editor['variant'];
        self::assertIsArray($variant);
        self::assertNotEmpty($variant);

        self::assertArrayHasKey('options', $variant);
        self::assertIsString($variant['options']);

        $options = json_decode($variant['options'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($options);
        self::assertNotEmpty($options);

        self::assertArrayHasKey('extensions', $options);
        self::assertIsArray($options['extensions']);

        $extensions = array_filter(
            $options['extensions'],
            function (array $extension): bool {
                return $extension['name'] === extension::get_extension_name();
            }
        );

        self::assertCount(1, $extensions);
        $multi_lang_ext = reset($extensions);

        self::assertIsArray($multi_lang_ext);
        self::assertArrayHasKey('options', $multi_lang_ext);

        self::assertIsArray($multi_lang_ext['options']);
        self::assertArrayHasKey('is_active', $multi_lang_ext['options']);
        self::assertFalse($multi_lang_ext['options']['is_active']);
    }

    /**
     * @return void
     */
    public function test_get_extension_with_status_active_as_true_due_to_multi_lang_is_enabled(): void {
        // Enable filter at context system.
        $context_system = context_system::instance();

        $db = builder::get_db();
        $record = new stdClass();
        $record->filter = 'multilang';
        $record->contextid = $context_system->id;
        $record->active = TEXTFILTER_ON;

        $db->insert_record('filter_active', $record);
        $this->setAdminUser();

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_system->id,
                'variant_name' => variant_name::SIMPLE,
                'format' => FORMAT_JSON_EDITOR,
                'framework' => 'tui',
                'extra_extensions' => json_encode([
                    [
                        'name' => extension::get_extension_name(),
                        'options' => [
                            'context_id' => $context_system->id,
                        ]
                    ]
                ])
            ]
        );

        self::assertEmpty($result->errors);
        self::assertIsArray($result->data);
        self::assertNotEmpty($result->data);

        self::assertArrayHasKey('editor', $result->data);

        $editor = $result->data['editor'];
        self::assertIsArray($editor);
        self::assertArrayHasKey('variant', $editor);

        $variant = $editor['variant'];
        self::assertIsArray($variant);
        self::assertNotEmpty($variant);

        self::assertArrayHasKey('options', $variant);
        self::assertIsString($variant['options']);

        $options = json_decode($variant['options'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($options);
        self::assertNotEmpty($options);

        self::assertArrayHasKey('extensions', $options);
        self::assertIsArray($options['extensions']);

        $extensions = array_filter(
            $options['extensions'],
            function (array $extension): bool {
                return $extension['name'] === extension::get_extension_name();
            }
        );

        self::assertCount(1, $extensions);
        $multi_lang_ext = reset($extensions);

        self::assertIsArray($multi_lang_ext);
        self::assertArrayHasKey('options', $multi_lang_ext);

        self::assertIsArray($multi_lang_ext['options']);
        self::assertArrayHasKey('is_active', $multi_lang_ext['options']);
        self::assertTrue($multi_lang_ext['options']['is_active']);
    }

    /**
     * @return void
     */
    public function test_get_extension_with_status_active_as_true_due_to_multi_lang_is_enabled_at_lower_context(): void {
        // Enable filter at context system.
        $context_system = context_system::instance();
        $db = builder::get_db();

        $system_record = new stdClass();
        $system_record->filter = 'multilang';
        $system_record->contextid = $context_system->id;
        $system_record->active = TEXTFILTER_OFF;

        $db->insert_record('filter_active', $system_record);
        $this->setAdminUser();

        // Create a course so that we can have a course context
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        $course_record = new stdClass();
        $course_record->filter = 'multilang';
        $course_record->contextid = $context_course->id;
        $course_record->active = TEXTFILTER_ON;
        $db->insert_record('filter_active', $course_record);

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_course->id,
                'variant_name' => variant_name::SIMPLE,
                'format' => FORMAT_JSON_EDITOR,
                'framework' => 'tui',
                'extra_extensions' => json_encode([
                    [
                        'name' => extension::get_extension_name(),
                        'options' => [
                            'context_id' => $context_course->id,
                        ]
                    ]
                ])
            ]
        );

        self::assertEmpty($result->errors);
        self::assertIsArray($result->data);
        self::assertNotEmpty($result->data);

        self::assertArrayHasKey('editor', $result->data);

        $editor = $result->data['editor'];
        self::assertIsArray($editor);
        self::assertArrayHasKey('variant', $editor);

        $variant = $editor['variant'];
        self::assertIsArray($variant);
        self::assertNotEmpty($variant);

        self::assertArrayHasKey('options', $variant);
        self::assertIsString($variant['options']);

        $options = json_decode($variant['options'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($options);
        self::assertNotEmpty($options);

        self::assertArrayHasKey('extensions', $options);
        self::assertIsArray($options['extensions']);

        $extensions = array_filter(
            $options['extensions'],
            function (array $extension): bool {
                return $extension['name'] === extension::get_extension_name();
            }
        );

        self::assertCount(1, $extensions);
        $multi_lang_ext = reset($extensions);

        self::assertIsArray($multi_lang_ext);
        self::assertArrayHasKey('options', $multi_lang_ext);

        self::assertIsArray($multi_lang_ext['options']);
        self::assertArrayHasKey('is_active', $multi_lang_ext['options']);
        self::assertTrue($multi_lang_ext['options']['is_active']);
    }

    /**
     * @return void
     */
    public function test_get_extension_with_compact_mode(): void {
        $context_system = context_system::instance();
        $this->setAdminUser();

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_system->id,
                'variant_name' => variant_name::SIMPLE,
                'format' => FORMAT_JSON_EDITOR,
                'framework' => 'tui',
                'extra_extensions' => json_encode([
                    [
                        'name' => extension::get_extension_name(),
                        'options' => [
                            'context_id' => $context_system->id,
                            'compact' => true
                        ]
                    ]
                ])
            ]
        );

        self::assertEmpty($result->errors);
        self::assertIsArray($result->data);
        self::assertNotEmpty($result->data);

        self::assertArrayHasKey('editor', $result->data);

        $editor = $result->data['editor'];
        self::assertIsArray($editor);
        self::assertArrayHasKey('variant', $editor);

        $variant = $editor['variant'];
        self::assertIsArray($variant);
        self::assertNotEmpty($variant);

        self::assertArrayHasKey('options', $variant);
        self::assertIsString($variant['options']);

        $options = json_decode($variant['options'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($options);
        self::assertNotEmpty($options);

        self::assertArrayHasKey('extensions', $options);
        self::assertIsArray($options['extensions']);

        $extensions = array_filter(
            $options['extensions'],
            function (array $extension): bool {
                return $extension['name'] === extension::get_extension_name();
            }
        );

        self::assertCount(1, $extensions);
        $multi_lang_ext = reset($extensions);

        self::assertIsArray($multi_lang_ext);
        self::assertArrayHasKey('options', $multi_lang_ext);

        self::assertIsArray($multi_lang_ext['options']);
        self::assertArrayHasKey('compact', $multi_lang_ext['options']);
        self::assertTrue($multi_lang_ext['options']['compact']);

        self::assertArrayHasKey('placeholder_resolver_class_name', $multi_lang_ext['options']);
        self::assertNull($multi_lang_ext['options']['placeholder_resolver_class_name']);
    }

    /**
     * @return void
     */
    public function test_get_extension_with_resolver_class_name(): void {
        $context_system = context_system::instance();
        $this->setAdminUser();

        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => $context_system->id,
                'variant_name' => variant_name::SIMPLE,
                'format' => FORMAT_JSON_EDITOR,
                'framework' => 'tui',
                'extra_extensions' => json_encode([
                    [
                        'name' => extension::get_extension_name(),
                        'options' => [
                            'context_id' => $context_system->id,
                            'placeholder_resolver_class_name' => 'mock_class'
                        ]
                    ]
                ])
            ]
        );

        self::assertEmpty($result->errors);
        self::assertIsArray($result->data);
        self::assertNotEmpty($result->data);

        self::assertArrayHasKey('editor', $result->data);

        $editor = $result->data['editor'];
        self::assertIsArray($editor);
        self::assertArrayHasKey('variant', $editor);

        $variant = $editor['variant'];
        self::assertIsArray($variant);
        self::assertNotEmpty($variant);

        self::assertArrayHasKey('options', $variant);
        self::assertIsString($variant['options']);

        $options = json_decode($variant['options'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($options);
        self::assertNotEmpty($options);

        self::assertArrayHasKey('extensions', $options);
        self::assertIsArray($options['extensions']);

        $extensions = array_filter(
            $options['extensions'],
            function (array $extension): bool {
                return $extension['name'] === extension::get_extension_name();
            }
        );

        self::assertCount(1, $extensions);
        $multi_lang_ext = reset($extensions);

        self::assertIsArray($multi_lang_ext);
        self::assertArrayHasKey('options', $multi_lang_ext);

        self::assertIsArray($multi_lang_ext['options']);
        self::assertArrayHasKey('compact', $multi_lang_ext['options']);
        self::assertFalse($multi_lang_ext['options']['compact']);

        self::assertArrayHasKey('placeholder_resolver_class_name', $multi_lang_ext['options']);
        self::assertEquals(
            "mock_class",
            $multi_lang_ext['options']['placeholder_resolver_class_name']
        );
    }
}