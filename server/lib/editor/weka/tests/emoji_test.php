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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\json_editor;

class editor_weka_emoji_testcase extends advanced_testcase {
    /**
     * Test to assure that emoji node works as expected.
     * @return void
     */
    public function test_node(): void {
        $json = json_encode(
            [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'emoji',
                        'attrs' => [
                            'id' => 11,
                            'shortcode' => '1F60D'
                        ]
                    ]
                ]
            ]
        );

        $editor = json_editor::default();
        // Test that we can convert to HTML.
        $html = $editor->to_html($json);
        $this->assertStringContainsString("&#x1F60D;", $html);

        // Test that we can convert to TEXT.
        $text = $editor->to_text($json);
        $this->assertStringContainsString('😍', $text);
    }
}