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

use core\json_editor\node\video;

class core_json_editor_video_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'woops.mp4',
                    'url' => 'http://example.com',
                    'mime_type' => 'video/mp'
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_missing_keys(): void {
        $this->assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'woops.mp4',
                    'url' => 'http://example.com',
                ]
            ])
        );

        $this->assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'woops.mp4',
                    'mime_type' => 'video/mp4'
                ]
            ])
        );

        $this->assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'url' => 'http://example.com',
                    'mime_type' => 'video/mp4'
                ]
            ])
        );

        $this->assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'woops.mp4'
                ]
            ])
        );

        $this->assertFalse(
            video::validate_schema(['type' => video::get_type()])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_extra_keys(): void {
        $this->assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'file.mp4',
                    'url' => 'http://example.com',
                    'mime_type' => 'audio/mp4',
                    'extra_keys' => 'd'
                ],
            ])
        );

        $this->assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'file.mp4',
                    'url' => 'http://example.com',
                    'mime_type' => 'audio/mp4',
                ],
                'ddd' => 'dew'
            ])
        );

        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @return void
     */
    public function test_clean_raw_node(): void {
        $data = [
            'type' => video::get_type(),
            'attrs' => [
                'filename' => 'file.mp4',
                'url' => 'http://example.com',
                'mime_type' => 'audio/mp4'
            ]
        ];

        $cleaned = video::clean_raw_node($data);
        $this->assertSame($data, $cleaned);
    }

    /**
     * @return void
     */
    public function test_clean_invalid_node(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'Coding error detected, it must be fixed by a programmer: Invalid node structure (video)'
        );

        video::clean_raw_node(['type' => video::get_type()]);
    }

    /**
     * @return void
     */
    public function test_clean_invalid_data_node(): void {
        $data = [
            'type' => video::get_type(),
            'attrs' => [
                'filename' => '..',
                'url' => 'http://example.com',
                'mime_type' => 'mmoooo'
            ]
        ];

        $cleaned = video::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);
        $this->assertArrayHasKey('attrs', $cleaned);

        $this->assertArrayHasKey('filename', $cleaned['attrs']);
        $this->assertEquals('', $cleaned['attrs']['filename']);

        $this->assertArrayHasKey('url', $cleaned['attrs']);
        $this->assertEquals('http://example.com', $cleaned['attrs']['url']);

        $this->assertArrayHasKey('mime_type', $cleaned['attrs']);
        $this->assertEquals('mmoooo', $cleaned['attrs']['mime_type']);
    }

    /**
     * @return void
     */
    public function test_clean_node_with_invalid_url(): void {
        $this->assertNull(
            video::clean_raw_node([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http:://example.com',
                    'mime_type' => 'audio/mp4'
                ]
            ])
        );

        $this->assertNull(
            video::clean_raw_node([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'mailto://admin@example.com',
                    'mime_type' => 'audio/mp4'
                ],
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_valid_subtitle(): void {
        self::assertTrue(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'video/mp4',
                    'subtitle' => [
                        'url' => 'http://example.com/data_subtitle.vtt',
                        'filename' => 'data_subtitle.vtt'
                    ]
                ]
            ])
        );

        self::assertTrue(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'ddd',
                    'subtitle' => null
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_invalid_subtitle(): void {
        self::assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'ddd',
                    'subtitle' => 'xwz'
                ]
            ])
        );

        self::assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'ddd',
                    'subtitle' => 45
                ]
            ])
        );

        self::assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'ddd',
                    'subtitle' => true
                ]
            ])
        );

        self::assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'ddd',
                    'subtitle' => []
                ]
            ])
        );

        self::assertFalse(
            video::validate_schema([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'ddd',
                    'subtitle' => [
                        'db' => '192.168.0.1'
                    ]
                ]
            ])
        );

        // We don't really have to assert the debugging message, but we know for sure that debugging message
        // is emitted.
        $this->resetDebugging();
    }

    /**
     * @return void
     */
    public function test_clean_video_node_with_invalid_subtitle_node(): void {
        self::assertEquals(
            [
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'video/mp4 ',
                    'subtitle' => [
                        'url' => null,
                        'filename' => 'kaboom_da_rock'
                    ]
                ]
            ],

            video::clean_raw_node([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'video/mp4 ',
                    'subtitle' => [
                        'url' => '<script>alert("hi there")</script>',
                        'filename' => 'kaboom_da_rock'
                    ]
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_clean_video_node_with_valid_subtitle_node(): void {
        self::assertEquals(
            [
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'video/mp4 ',
                    'subtitle' => [
                        'url' => 'http://example.com/kaboom_da_rock',
                        'filename' => 'kaboom_da_rock'
                    ]
                ]
            ],

            video::clean_raw_node([
                'type' => video::get_type(),
                'attrs' => [
                    'filename' => 'data.mp4',
                    'url' => 'http://example.com/data.mp4',
                    'mime_type' => 'video/mp4 ',
                    'subtitle' => [
                        'url' => 'http://example.com/kaboom_da_rock',
                        'filename' => 'kaboom_da_rock'
                    ]
                ]
            ])
        );
    }
}