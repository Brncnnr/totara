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

use core\json_editor\node\image;
use totara_tui\json_editor\output_node\image as image_output;

class totara_tui_json_editor_image_node_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_output_component(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $context = context_user::instance($USER->id);

        $file_record = new stdClass();
        $file_record->itemid = file_get_unused_draft_itemid();
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filename = 'something_mean.png';
        $file_record->filepath = '/';
        $file_record->contextid = $context->id;

        $fs = get_file_storage();
        $stored_file = $fs->create_file_from_string($file_record, 'Nakupuu');

        $raw_node = image::create_raw_node_from_image($stored_file);
        $node = image::from_node($raw_node);

        $draft_url = moodle_url::make_draftfile_url(
            $file_record->itemid,
            $file_record->filepath,
            $file_record->filename
        );

        $attributes = htmlspecialchars(
            json_encode([
                'filename' => $stored_file->get_filename(),
                'url' => $draft_url->out(false),
                'alt-text' => '',
                'display-size' => null,
            ])
        );

        $expected = /** @lang text */
            "<span data-tui-component=\"tui/components/json_editor/nodes/ImageBlock\" " .
            "data-tui-props=\"{$attributes}\"></span>";

        $output = new image_output($node);
        $this->assertSame($expected, $output->render_tui_component_content());
    }
}