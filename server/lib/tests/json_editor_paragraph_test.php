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

use core\json_editor\formatter\default_formatter;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core\json_editor\node\attachment;

class core_json_editor_paragraph_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            paragraph::validate_schema([
                'type' => paragraph::get_type(),
                'content' => [
                    [
                        'type' => text::get_type(),
                        'text' => 'this is text'
                    ]
                ],
            ])
        );

        $this->assertTrue(
            paragraph::validate_schema([
                'type' => paragraph::get_type(),
                'content' => []
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_block_node(): void {
        $this->assertFalse(
            paragraph::validate_schema([
                'type' => paragraph::get_type(),
                'content' => [
                    [
                        'type' => attachment::get_type(),
                        'attrs' => [
                            'filename' => 'somefile.mp3',
                            'url' => 'http://example.com'
                        ]
                    ]
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_invalid_content_node(): void {
        $this->assertFalse(
            paragraph::validate_schema([
                'type' => paragraph::get_type(),
                'content' => ['12', '2', '3']
            ])
        );

        $this->assertFalse(
            paragraph::validate_schema([
                'type' => paragraph::get_type(),
                'content' => [
                    [
                        'type' => text::get_type(),
                        'text' => 'dewq',
                        'deew' => 'de'
                    ]
                ]
            ])
        );

        $this->assertDebuggingCalled();
    }

    /**
     * @return void
     */
    public function test_clean_raw_node(): void {
        $data = [
            'type' => paragraph::get_type(),
            'content' => null
        ];

        $cleaned = paragraph::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);

        $this->assertArrayHasKey('type', $cleaned);
        $this->assertArrayHasKey('content', $cleaned);
        $this->assertNotNull($cleaned['content']);
    }

    /**
     * @return void
     */
    public function test_clean_raw_node_with_valid_data(): void {
        $data = [
            'type' => paragraph::get_type(),
            'content' => [
                'ddd' => [
                    'type' => text::get_type(),
                    'text' => 'gelllo world'
                ]
            ]
        ];

        $cleaned = paragraph::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);

        $this->assertArrayHasKey('type', $cleaned);
        $this->assertArrayHasKey('content', $cleaned);
        $this->assertArrayNotHasKey('ddd', $cleaned['content']);

        $this->assertNotEmpty($cleaned['content']);
        $text = reset($cleaned['content']);

        $this->assertArrayHasKey('type', $text);
        $this->assertArrayHasKey('text', $text);
        $this->assertSame('gelllo world', $text['text']);
    }

    /**
     * @return void
     */
    public function test_clean_raw_node_with_empty_attrs(): void {
        $data = [
            'type' => paragraph::get_type(),
            'attrs' => []
        ];

        $cleaned = paragraph::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);

        $this->assertArrayNotHasKey('attrs', $cleaned);
    }

    /**
     * Test alignment works through to_html for paragraph
     * @return void
     */
    public function test_alignment_options(): void {
        $formatter = new default_formatter();

        // Right
        $node = [
            'type' => 'paragraph',
            'attrs' => [
                'align' => 'right',
                'level' => 1,
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'This is a test'
                ],
            ],
        ];

        $paragraph = paragraph::from_node($node);
        $html = $paragraph->to_html($formatter);
        $this->assertStringContainsString('<p style="text-align:right;">This is a test</p>', $html);

        // Left
        $node = [
            'type' => 'paragraph',
            'attrs' => [
                'align' => 'left',
                'level' => 1,
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'This is a test'
                ],
            ],
        ];

        $paragraph = paragraph::from_node($node);
        $html = $paragraph->to_html($formatter);
        $this->assertStringContainsString('<p style="text-align:left;">This is a test</p>', $html);

        // Center
        $node = [
            'type' => 'paragraph',
            'attrs' => [
                'align' => 'center',
                'level' => 1,
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'This is a test'
                ],
            ],
        ];

        $paragraph = paragraph::from_node($node);
        $html = $paragraph->to_html($formatter);
        $this->assertStringContainsString('<p style="text-align:center;">This is a test</p>', $html);
    }
}