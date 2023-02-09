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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\json_editor;

class editor_weka_list_testcase extends advanced_testcase {

    /**
     * Test to assure that ordered list node works as expected.
     * @return void
     */
    public function test_ordered_list_node(): void {
        $json = json_encode(
            [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'ordered_list',
                        'attrs' => [
                            'order' => 1
                        ],
                        'content' => [
                            [
                                'type' => 'list_item',
                                'content' => [
                                    [
                                        'type' => 'paragraph',
                                        'content' => [
                                            [
                                                'type' => 'text',
                                                'text' => 'This is a list item'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $editor = json_editor::default();

        // Test that we can convert to HTML.
        $html = $editor->to_html($json);
        $this->assertStringContainsString('<ol type="1"><li><p>This is a list item</p></li></ol>', $html);
    }

    /**
     * Test to assure that bullet list node works as expected.
     * @return void
     */
    public function test_bullet_list_node(): void {
        $json = json_encode(
            [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'bullet_list',
                        'content' => [
                            [
                                'type' => 'list_item',
                                'content' => [
                                    [
                                        'type' => 'paragraph',
                                        'content' => [
                                            [
                                                'type' => 'text',
                                                'text' => 'This is a list item'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $editor = json_editor::default();

        // Test that we can convert to HTML.
        $html = $editor->to_html($json);
        $this->assertStringContainsString('<ul><li><p>This is a list item</p></li></ul>', $html);
    }
}