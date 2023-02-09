<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */

use core\json_editor\formatter\default_formatter;
use core\json_editor\node\image;
use core\json_editor\node\layout_block;
use core\json_editor\node\layout_column;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core_phpunit\testcase;

class core_json_editor_layout_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_schema(): void {
        // typical structure
        $this->assertTrue(
            layout_block::validate_schema([
                'type' => layout_block::get_type(),
                'content' => [
                    [
                        'type' => layout_column::get_type(),
                        'content' => [paragraph::create_json_node_from_text('Hello world')]
                    ]
                ]
            ])
        );

        // block content must be layout_column
        $this->assertFalse(
            layout_block::validate_schema([
                'type' => image::get_type(),
                'content' => [paragraph::create_json_node_from_text('Hello world')],
            ])
        );

        // layout_column content must be blocks
        $this->assertFalse(
            layout_block::validate_schema([
                'type' => image::get_type(),
                'content' => [
                    [
                        'type' => layout_column::get_type(),
                        'content' => [
                            // inline node:
                            text::create_json_node_from_text('Hello world')
                        ]
                    ]
                ],
            ])
        );
    }

    /**
     * @return void
     */
    public function test_html_output(): void {
        $formatter = new default_formatter();

        $node = layout_block::from_node([
            'type' => layout_block::get_type(),
            'content' => [
                [
                    'type' => layout_column::get_type(),
                    'content' => [paragraph::create_json_node_from_text('Hello world')]
                ],
                [
                    'type' => layout_column::get_type(),
                    'content' => [paragraph::create_json_node_from_text('Hello world')]
                ],
            ]
        ]);

        $this->assertEquals(
            '<div class="jsoneditor-layout-block">' .
                '<div class="jsoneditor-layout-column"><p>Hello world</p></div>' .
                '<div class="jsoneditor-layout-column"><p>Hello world</p></div>' .
            '</div>',
            $node->to_html($formatter)
        );

        // With sidebar
        $node = layout_block::from_node([
            'type' => layout_block::get_type(),
            'content' => [
                [
                    'type' => layout_column::get_type(),
                    'attrs' => [
                        'type' => 'sidebar',
                    ],
                    'content' => [paragraph::create_json_node_from_text('Hello world')]
                ],
                [
                    'type' => layout_column::get_type(),
                    'content' => [paragraph::create_json_node_from_text('Hello world')]
                ],
            ]
        ]);

        $this->assertEquals(
            '<div class="jsoneditor-layout-block">' .
                '<div class="jsoneditor-layout-column jsoneditor-layout-column--sidebar"><p>Hello world</p></div>' .
                '<div class="jsoneditor-layout-column"><p>Hello world</p></div>' .
            '</div>',
            $node->to_html($formatter)
        );
    }
}
