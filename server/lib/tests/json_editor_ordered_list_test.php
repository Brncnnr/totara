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

use core\json_editor\node\ordered_list;
use core\json_editor\node\list_item;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;

class core_json_editor_ordered_list_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            ordered_list::validate_schema([
                'type' => ordered_list::get_type(),
                'content' => [
                    [
                        'type' => list_item::get_type(),
                        'content' => [
                            [
                                'type' => paragraph::get_type(),
                                'content' => [
                                    [
                                        'type' => text::get_type(),
                                        'text' => 'This is text'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'attrs' => [
                    'order' => 1
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_clean_raw_node(): void {
        $data = [
            'type' => ordered_list::get_type(),
            'content' => null,
            'attrs' => [
                'order' => 'x'
            ]
        ];

        $cleaned = ordered_list::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);

        $this->assertArrayHasKey('content', $cleaned);
        $this->assertNotNull($cleaned['content']);
        $this->assertEmpty($cleaned['content']);

        $this->assertArrayHasKey('attrs', $cleaned);
        $this->assertArrayHasKey('order', $cleaned['attrs']);
        $this->assertEquals('', $cleaned['attrs']['order']);
    }
}