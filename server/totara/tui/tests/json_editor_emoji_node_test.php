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

use core\json_editor\node\emoji;
use totara_tui\json_editor\output_node\emoji as emoji_output;

class totara_tui_json_editor_emoji_node_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_output_component(): void {
        $raw_node = emoji::create_raw_node('dota-god');

        /** @var emoji $node */
        $node = emoji::from_node($raw_node);
        $output_node = new emoji_output($node);

        $attributes = htmlspecialchars(json_encode(['short-code' => 'dota-god']));
        $expeceted = /** @lang text */
            "<span data-tui-component=\"tui/components/json_editor/nodes/Emoji\" " .
            "data-tui-props=\"{$attributes}\"></span>";

        $this->assertEquals($expeceted, $output_node->render_tui_component_content());
    }
}