<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_tui
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\node\text;
use totara_tui\json_editor\formatter\formatter;
use core\json_editor\node\mention;
use core\json_editor\node\paragraph;

class totara_tui_json_editor_formatter_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_to_html(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $document = [
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('hello '),
                mention::create_raw_node($user->id)
            ]
        ];

        $formatter = new formatter();

        $attributes = htmlspecialchars(
            json_encode([
                'user-id' => (int) $user->id,
                'fullname' => fullname($user)
            ])
        );

        $expected = /** @lang text */
            "<div class=\"tui-rendered\">" .
            "<p>hello </p>" .
            "<span data-tui-component=\"tui/components/json_editor/nodes/Mention\" " .
            "data-tui-props=\"{$attributes}\"></span>" .
            "</div>";

        $rendered_content = $formatter->to_html($document);
        $this->assertSame($expected, $rendered_content);
    }

    /**
     * @return void
     */
    public function test_to_text(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $document = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [text::create_json_node_from_text('heading 1')]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        text::create_json_node_from_text('hello '),
                        mention::create_raw_node($user->id)
                    ]
                ]
            ]
        ];

        $formatter = new formatter();
        $fullname = fullname($user);

        $expected = "# heading 1\nhello @{$fullname}\n\n";
        $this->assertEquals($expected, $formatter->to_text($document));
    }

    /**
     * @return void
     */
    public function test_to_markdown(): void {
        $document = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        text::create_json_node_from_text('abc'),
                    ]
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        text::create_json_node_from_text('def'),
                    ]
                ],
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [text::create_json_node_from_text('heading 1')]
                ],
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 2],
                    'content' => [text::create_json_node_from_text('heading 2')]
                ],
            ]
        ];

        $formatter = new formatter();
        $expected = "abc\n\ndef\n\nHEADING 1\n\nHEADING 2\n\n";
        $text = markdown_to_html($formatter->to_text($document));
        $text = html_to_text($text);
        $this->assertEquals($expected, $text);
    }
}