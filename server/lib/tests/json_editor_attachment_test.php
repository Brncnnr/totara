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

use core\json_editor\node\attachment;

class core_json_editor_attachment_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_that_is_valid(): void {
        $data = [
            'type' => attachment::get_type(),
            'attrs' => [
                'filename' => 'some_file.png',
                'url' => 'http://example.com',
                'size' => 1920
            ]
        ];

        $result = attachment::validate_schema($data);
        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_optional_field(): void {
        $result = attachment::validate_schema([
            'type' => attachment::get_type(),
            'attrs' => [
                'filename' => 'some_file.png',
                'url' => 'http://example.com',
                'size' => 150,
                'option' => []
            ]
        ]);

        $this->assertTrue($result);
    }

    /**
     * Make sure that the key `options` is not allowed in attachment node.
     * @return void
     */
    public function test_validate_schema_with_options_keys(): void {
        $this->assertFalse(
            attachment::validate_schema([
                'type' => attachment::get_type(),
                'attrs' => [
                    'filename' => 'some_file.png',
                    'url' => 'http://example.com',
                    'size' => 150,
                    'options' => []
                ]
            ])
        );

        $this->assertDebuggingCalled();
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_keys_that_not_in_schema(): void {
        $result = attachment::validate_schema([
            'type' => attachment::get_type(),
            'attrs' => [
                'filename' => 'some_file.png',
                'url' => 'http://example.com',
                'size' => 1920,
                'oops' => '15'
            ],
        ]);

        $this->assertFalse($result);
        $this->assertDebuggingCalled();

        $result = attachment::validate_schema([
            'type' => attachment::get_type(),
            'attrs' => [
                'filename' => 'some_file.png',
                'url' => 'http://example.com',
                'size' => 1920,
            ],
            'random_key' => 15
        ]);

        $this->assertFalse($result);
        $this->assertDebuggingCalled();
    }

    /**
     * @return void
     */
    public function test_validate_schema_missing_key(): void {
        $result = attachment::validate_schema([
            'type' => attachment::get_type(),
            'schema' => 'woops'
        ]);

        $this->assertFalse($result);
    }

    /**
     * @return void
     */
    public function test_get_invalid_file_url(): void {
        $node = attachment::from_node([
            'type' => attachment::get_type(),
            'attrs' => [
                'filename' => 'file.txt',
                'url' => '@@PLUGINFILE@@/file.txt',
                'size' => 42
            ]
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The file url had not been rewritten yet");

        $node->get_file_url();
    }

    /**
     * @return void
     */
    public function test_get_valid_file_url(): void {
        $node = attachment::from_node([
            'type' => attachment::get_type(),
            'attrs' => [
                'filename' => 'file.txt',
                'url' => 'http://example.com/file.txt',
                'size' => 192
            ]
        ]);

        $file_url = $node->get_file_url(true);

        self::assertEquals(1, $file_url->get_param('forcedownload'));
        self::assertEquals('example.com', $file_url->get_host());
        self::assertEquals('http', $file_url->get_scheme());
    }
}