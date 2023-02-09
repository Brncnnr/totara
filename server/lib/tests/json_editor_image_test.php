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
use core\json_editor\node\image;

class core_json_editor_image_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            image::validate_schema([
                'type' => image::get_type(),
                'attrs' => [
                    'filename' => 'file.png',
                    'alttext' => 'woops',
                    'url' => 'http://example.com'
                ],
            ])
        );

        $this->assertTrue(
            image::validate_schema([
                'type' => image::get_type(),
                'attrs' => [
                    'filename' => 'file.png',
                    'alttext' => 'woops',
                    'url' => 'http://example.com',
                    'display_size' => 'small',
                ],
            ])
        );
    }

    public function test_html_output(): void {
        $formatter = new default_formatter();

        $node = [
            'type' => image::get_type(),
            'attrs' => [
                'filename' => 'file.png',
                'alttext' => '',
                'url' => 'http://example.com/img.jpg',
            ],
        ];

        $heading = image::from_node($node);
        $this->assertEquals(
            '<div class="jsoneditor-image-block">' .
                '<img src="http://example.com/img.jpg" alt="" class="jsoneditor-image-block__img" /></div>',
            $heading->to_html($formatter)
        );

        $node = [
            'type' => image::get_type(),
            'attrs' => [
                'filename' => 'file.png',
                'alttext' => '',
                'url' => 'http://example.com/img.jpg',
                'display_size' => 'small',
            ],
        ];

        $heading = image::from_node($node);
        $this->assertEquals(
            '<div class="jsoneditor-image-block jsoneditor-image-block--display-size jsoneditor-image-block--display-size-small">' .
                '<img src="http://example.com/img.jpg" alt="" class="jsoneditor-image-block__img" /></div>',
            $heading->to_html($formatter)
        );
    }
}
