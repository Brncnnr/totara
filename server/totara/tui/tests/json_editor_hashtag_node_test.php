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

use core\json_editor\node\hashtag;
use totara_tui\json_editor\output_node\hashtag as hashtag_outpout_node;

class totara_tui_json_editor_hashtag_node_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_output_component(): void {
        $raw_node = hashtag::create_raw_node('this-is-real-life');
        $node = hashtag::from_node($raw_node);

        $output = new hashtag_outpout_node($node);

        $catalog_url = new moodle_url('/totara/catalog/index.php', ['catalog_fts' => 'this-is-real-life']);
        $attributes = htmlspecialchars(
            json_encode([
                'url' => $catalog_url->out(false),
                'text' => get_string('hashtag', 'editor_weka', 'this-is-real-life')
            ])
        );

        $expected = /** @lang text */
            "<span data-tui-component=\"tui/components/json_editor/nodes/Hashtag\" " .
            "data-tui-props=\"{$attributes}\"></span>";

        $this->assertSame($expected, $output->render_tui_component_content());
    }
}