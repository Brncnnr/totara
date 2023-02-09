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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\document;
use core\json_editor\node\attachment;
use core\orm\query\builder;

class editor_weka_prepare_draft_area_files_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_prepare_draft_area_files(): void {
        global $USER, $CFG, $DB;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();

        $draftid = file_get_unused_draft_itemid();
        $context = \context_user::instance($USER->id);

        $fs = get_file_storage();
        $attachments = [];

        for ($i = 0; $i < 4; $i++) {
            $record = new \stdClass();
            $record->filename = "file_{$i}.png";
            $record->filepath = '/';
            $record->component = 'user';
            $record->filearea = 'draft';
            $record->itemid = $draftid;
            $record->contextid = $context->id;

            $file = $fs->create_file_from_string($record, 'content of the file');
            $attachments[] = [
                'type' => 'attachment',
                'attrs' => [
                    'filename' => $file->get_filename(),
                ]
            ];
        }

        $category = builder::table('course_categories')->order_by('id')->first();

        $courserecord = new \stdClass();
        $courserecord->fullname = 'Hello 101';
        $courserecord->shortname = 'abcde';
        $courserecord->category = $category->id;
        $courserecord->summary_editor = [
            'text' => json_encode(
                [
                    'type' => 'doc',
                    'content' => [
                        [
                            'type' => 'attachments',
                            'content' => $attachments
                        ]
                    ]
                ]
            ),
            'format' => FORMAT_JSON_EDITOR,
            'itemid' => $draftid
        ];

        $cloned = fullclone($courserecord);
        $course = create_course($cloned, ['maxfiles' => 5]);

        $this->assertObjectHasAttribute('summary', $course);
        $coursecontext = \context_course::instance($course->id);
        $files = $fs->get_area_files(
            $coursecontext->id,
            'course',
            'summary',
            0
        );

        $files = array_filter($files, function (\stored_file $file): bool {
            return !$file->is_directory();
        });

        $this->assertCount(4, $files);
        $course = $DB->get_record('course', ['id' => $course->id]);

        // Update the text field format temporarily, until the bug is being fixed.
        $course->summaryformat = FORMAT_JSON_EDITOR;

        $options = [
            'maxfiles' => 5,
            'context' => $coursecontext
        ];

        file_prepare_standard_editor($course, 'summary', $options, $coursecontext, 'course', 'summary', 0);

        $this->assertObjectHasAttribute('summary_editor', $course);
        $this->assertIsArray($course->summary_editor);

        $this->assertArrayHasKey('text', $course->summary_editor);
        $document = document::create($course->summary_editor['text']);

        $nodes = $document->find_raw_nodes(attachment::get_type());
        $this->assertCount(4, $nodes);

        $this->assertObjectHasAttribute('summary_editor', $course);
        $this->assertArrayHasKey('itemid', $course->summary_editor);

        $new_draft_id = $course->summary_editor['itemid'];

        foreach ($nodes as $node) {
            $this->assertArrayHasKey('attrs', $node);
            $attrs = $node['attrs'];

            $this->assertArrayNotHasKey('draftid', $attrs);

            $file = $fs->get_file($context->id, 'user', 'draft', $new_draft_id, '/', $attrs['filename']);
            $this->assertInstanceOf(\stored_file::class, $file);
        }
    }
}