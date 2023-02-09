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

use core\json_editor\node\attachments;
use totara_tui\json_editor\output_node\attachments as collection_output_node;

/**
 * Unit tests for attachments node output.
 */
class totara_tui_json_editor_attachments_node_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_format_text(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $context = context_user::instance($USER->id);
        $fs = get_file_storage();

        // Create 5 files.
        $files = [];

        for ($i = 0; $i < 5; $i++) {
            $file_record = new stdClass();
            $file_record->itemid = file_get_unused_draft_itemid();
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->filename = uniqid() . ".png";
            $file_record->filepath = '/';
            $file_record->contextid = $context->id;

            $files[] = $fs->create_file_from_string($file_record, 'something dumb ' . $i);
        }

        // Start creating the document.

        $document = [
            'type' => 'doc',
            'content' => [
                attachments::create_raw_node_from_list($files)
            ]
        ];

        $rendered_content = format_text(json_encode($document), FORMAT_JSON_EDITOR, ['formatter' => 'totara_tui']);
        $this->assertStringContainsString(
            'data-tui-component="tui/components/json_editor/nodes/AttachmentNodeCollection"',
            $rendered_content
        );
    }

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
        $file_record->filename = uniqid() . ".png";
        $file_record->filepath = '/';
        $file_record->contextid = $context->id;

        $fs = get_file_storage();
        $stored_file = $fs->create_file_from_string($file_record, 'something dumb');

        $raw_node = attachments::create_raw_node_from_list([$stored_file]);

        /** @var attachments $node */
        $node = attachments::from_node($raw_node);
        $output_node = new collection_output_node($node);

        $file_url = moodle_url::make_draftfile_url(
            $stored_file->get_itemid(),
            $stored_file->get_filepath(),
            $stored_file->get_filename(),
            true
        );

        $attributes = htmlspecialchars(
            json_encode([
                'files' => [
                    [
                        'size' => $stored_file->get_filesize(),
                        'filename' => $stored_file->get_filename(),
                        'download_url' => $file_url->out(false),
                    ]
                ]
            ])
        );

        $expected = /** @lang text */
            "<span data-tui-component=\"tui/components/json_editor/nodes/AttachmentNodeCollection\" " .
            "data-tui-props=\"{$attributes}\"></span>";

        $rendered_content = $output_node->render_tui_component_content();
        $this->assertEquals($expected, $rendered_content);
    }
}