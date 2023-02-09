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
use core\json_editor\node\heading;
use core\json_editor\node\text;
use core\json_editor\node\paragraph;

class core_json_editor_heading_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            heading::validate_schema([
                'type' => heading::get_type(),
                'attrs' => [
                    'level' => heading::LEVEL_ONE
                ],
                'content' => [
                    [
                        'type' => text::get_type(),
                        'text' => 'xccc ddew'
                    ]
                ],
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_missing_keys(): void {
        $this->assertFalse(
            heading::validate_schema([
                'type' => heading::get_type(),
                'attrs' => [],
                'content' => [
                    [
                        'type' => text::get_type(),
                        'text' => 'xccc ddew'
                    ]
                ],
            ])
        );

        $this->assertFalse(
            heading::validate_schema([
                'type' => heading::get_type(),
                'attrs' => [
                    'level' => heading::LEVEL_ONE
                ]
            ])
        );

        $this->assertFalse(
            heading::validate_schema([
                'type' => heading::get_type(),
                'content' => [],
            ])
        );
    }

    /**
     * Heading node should only contain the inline node rather than a block node.
     * This test to make sure that validate schema functionality will fail if the node inside
     * heading is an actual block node.
     *
     * @return void
     */
    public function test_validate_schema_that_contain_invalid_node(): void {
        $this->assertFalse(
            heading::validate_schema([
                'type' => heading::get_type(),
                'attrs' => [
                    'level' => heading::LEVEL_TWO
                ],
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => []
                    ]
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_invalid_node(): void {
        // Result will be true, because it contains invalid node, and we are skipping check
        // for it.
        $this->assertTrue(
            heading::validate_schema([
                'type' => heading::get_type(),
                'attrs' => [
                    'level' => heading::LEVEL_TWO,
                ],
                'content' => [
                    [
                        'type' => 'some_random_node'
                    ]
                ]
            ])
        );

        $messages = $this->getDebuggingMessages();
        $this->assertDebuggingCalled();

        $message = reset($messages);
        $this->assertStringContainsString('Cannot find class for node type \'some_random_node\'', $message->message);
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_extra_keys(): void {
        $this->assertFalse(
            heading::validate_schema([
                'type' => heading::get_type(),
                'attrs' => [
                    'level' => heading::LEVEL_TWO,
                    'something_else' => 'x'
                ],
                'content' => []
            ])
        );

        $this->assertFalse(
            heading::validate_schema([
                'type' => heading::get_type(),
                'attrs' => [
                    'level' => heading::LEVEL_TWO
                ],
                'content' => [],
                'something-else' => 'x'
            ])
        );

        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @return void
     */
    public function test_clean_raw_node_reset_content(): void {
        $raw_data = [
            'type' => heading::get_type(),
            'attrs' => [
                'level' => heading::LEVEL_ONE
            ],
            'content' => [
                'x_y_z' => [
                    'type' => text::get_type(),
                    'text' => 'This is something else '
                ],
                'z_y_x' => [
                    'type' => text::get_type(),
                    'text' => 'ddd ogogo'
                ]
            ]
        ];

        $cleaned_data = heading::clean_raw_node($raw_data);
        $this->assertArrayHasKey('content', $cleaned_data);
        $this->assertArrayNotHasKey('x_y_z', $cleaned_data['content']);
        $this->assertArrayNotHasKey('z_y_x', $cleaned_data['content']);

        $this->assertArrayHasKey(0, $cleaned_data['content']);
        $this->assertArrayHasKey(1, $cleaned_data['content']);
        $this->assertArrayNotHasKey(2, $cleaned_data['content']);
    }

    /**
     * Test alignment works through to_html for heading
     * @return void
     */
    public function test_alignment_options(): void {
        $formatter = new default_formatter();

        // Right
        $node = [
            'type' => 'heading',
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

        $heading = heading::from_node($node);
        $html = $heading->to_html($formatter);
        $this->assertStringContainsString('<h4 style="text-align:right">This is a test</h4>', $html);

        // Left
        $node = [
            'type' => 'heading',
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

        $heading = heading::from_node($node);
        $html = $heading->to_html($formatter);
        $this->assertStringContainsString('<h4 style="text-align:left">This is a test</h4>', $html);

        // Center
        $node = [
            'type' => 'heading',
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

        $heading = heading::from_node($node);
        $html = $heading->to_html($formatter);
        $this->assertStringContainsString('<h4 style="text-align:center">This is a test</h4>', $html);
    }
}