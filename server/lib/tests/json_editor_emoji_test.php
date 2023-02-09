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
use core\json_editor\node\emoji;

class core_json_editor_emoji_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            emoji::validate_schema([
                'type' => emoji::get_type(),
                'attrs' => [
                    'shortcode' => 'XD'
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_missing_keys(): void {
        $this->assertFalse(
            emoji::validate_schema([
                'type' => emoji::get_type(),
                'attrs' => []
            ])
        );

        $this->assertFalse(
            emoji::validate_schema([
                'type' => emoji::get_type()
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_extra_keys(): void {
        $this->assertFalse(
            emoji::validate_schema([
                'type' => emoji::get_type(),
                'attrs' => [
                    'shortcode' => 'wops',
                    'extra_key' => []
                ],
            ])
        );

        $this->assertFalse(
            emoji::validate_schema([
                'type' => emoji::get_type(),
                'attrs' => ['shortcode' => 'xd'],
                'woops' => 'x'
            ])
        );

        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @return void
     */
    public function test_clean_raw_node(): void {
        $data = [
            'type' => emoji::get_type(),
            'attrs' => [
                'shortcode' => '1F620'
            ]
        ];

        $cleaned = emoji::clean_raw_node($data);
        $this->assertSame($data, $cleaned);
    }

    /**
     * @return void
     */
    public function test_convert_to_html_text(): void {
        $node = [
            'type' => emoji::get_type(),
            'attrs' => [
                'shortcode' => '1F60A'
            ]
        ];

        $emoji = emoji::from_node($node);

        $formatter = new default_formatter();

        $this->assertEquals('😊', $emoji->to_text($formatter));
        $this->assertEquals('<span>&#x1F60A;</span>', $emoji->to_html($formatter));

        $converted_again = mb_convert_encoding($emoji->to_html($formatter), 'UTF-8', 'HTML-ENTITIES');
        $this->assertEquals('<span>😊</span>', $converted_again);
    }
}