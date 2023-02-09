<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

use core\json_editor\json_editor;

class core_json_editor_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_format_to_html(): void {
        $document = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'marks' => [
                                [
                                    'type' => 'strong'
                                ]
                            ],
                            'text' => 'ccc'
                        ]
                    ]
                ]
            ]
        ];

        $editor = json_editor::default();
        $content = /** @lang text */'<p><strong>ccc</strong></p>';
        $result = $editor->to_html($document);

        $this->assertStringContainsString($content, $result);
    }

    /**
     * @return void
     */
    public function test_format_to_html_from_text(): void {
        global $CFG;
        $json = file_get_contents("{$CFG->dirroot}/lib/tests/fixtures/json_editor/sample_one.json");

        $editor = json_editor::default();
        $result = $editor->to_html($json);
        $this->assertNotEmpty($result);
    }
}