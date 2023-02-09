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
 * @author Aaron Machin <aaron.machin@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\formatter\default_formatter;
use core\json_editor\node\figure_caption;
use core\json_editor\node\heading;
use core\json_editor\node\text;
use core_phpunit\testcase;

class core_json_editor_figure_caption_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            figure_caption::validate_schema([
                'type' => figure_caption::get_type(),
                'content' => [
                    [
                        'type' => text::get_type(),
                        'text' => 'ABC HAPPY AS CAN BE!',
                    ],
                ],
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_invalid_data(): void {
        $this->assertFalse(
            figure_caption::validate_schema([
                'type' => figure_caption::get_type(),
                'content' => [
                    [
                        'type' => heading::get_type(),
                        'attrs' => [
                            'level' => heading::LEVEL_ONE
                        ],
                        'content' => [
                            [
                                'type' => text::get_type(),
                                'text' => 'ABC HAPPY AS CAN BE!',
                            ],
                        ],
                    ],
                ],
            ])
        );
    }

    public function test_html_output(): void {
        $formatter = new default_formatter();

        $node = [
            'type' => figure_caption::get_type(),
            'content' => [
                [
                    'type' => text::get_type(),
                    'text' => 'ABC HAPPY AS CAN BE!',
                ],
            ],
        ];

        $caption = figure_caption::from_node($node);
        $this->assertEquals(
            '<figcaption class="jsoneditor-figcaption">ABC HAPPY AS CAN BE!</figcaption>',
            $caption->to_html($formatter)
        );
    }

    public function test_to_text_output(): void {
        $formatter = new default_formatter();

        $node = [
            'type' => figure_caption::get_type(),
            'content' => [
                [
                    'type' => text::get_type(),
                    'text' => 'ABC HAPPY AS CAN BE, YES SIR-Y!',
                ],
            ],
        ];

        $caption = figure_caption::from_node($node);
        $this->assertEquals(
            'ABC HAPPY AS CAN BE, YES SIR-Y!',
            $caption->to_text($formatter)
        );
    }
}
