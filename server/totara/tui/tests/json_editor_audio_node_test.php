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

use core\json_editor\node\audio;
use totara_tui\json_editor\output_node\audio as audio_output_node;

/**
 * Tests for outputting audion node
 */
class totara_tui_json_editor_audio_node_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_output_component(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $context = context_user::instance($USER->id);

        // Create audio file.
        $file_record = new stdClass();
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->itemid = file_get_unused_draft_itemid();
        $file_record->filename = 'some_audio.mp3';
        $file_record->mimetype = 'audio/mp3';
        $file_record->contextid = $context->id;
        $file_record->filepath = '/';

        $fs = get_file_storage();
        $stored_file = $fs->create_file_from_string($file_record, 'this is audio, buzz buzz buzz');

        $raw_node = audio::create_raw_node($stored_file);

        /** @var audio $node */
        $node = audio::from_node($raw_node);

        $output_node = new audio_output_node($node);
        $content = $output_node->render_tui_component_content();

        $attributes = htmlspecialchars(json_encode([
            'filename' => $stored_file->get_filename(),
            'url' => $node->get_file_url()->out(false),
            'mime-type' => $stored_file->get_mimetype()
        ]));

        $expected = /** @lang text */
            "<span data-tui-component=\"tui/components/json_editor/nodes/AudioBlock\" " .
            "data-tui-props=\"{$attributes}\"></span>";

        $this->assertEquals($expected, $content);
    }

    /**
     * @return void
     */
    public function test_format_text(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $context = context_user::instance($USER->id);

        // Create audio file.
        $file_record = new stdClass();
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->itemid = file_get_unused_draft_itemid();
        $file_record->filename = 'some_audio.mp3';
        $file_record->mimetype = 'audio/mp3';
        $file_record->contextid = $context->id;
        $file_record->filepath = '/';

        $fs = get_file_storage();
        $stored_file = $fs->create_file_from_string($file_record, 'this is audio, buzz buzz buzz');

        $document = [
            'type' => 'doc',
            'content' => [
                audio::create_raw_node($stored_file)
            ]
        ];

        $rendered_content = format_text(json_encode($document), FORMAT_JSON_EDITOR, ['formatter' => 'totara_tui']);
        $this->assertStringContainsString(
            'data-tui-component="tui/components/json_editor/nodes/AudioBlock"',
            $rendered_content
        );
    }
}