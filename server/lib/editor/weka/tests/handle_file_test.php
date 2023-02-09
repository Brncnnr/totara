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

class editor_weka_handle_file_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_processing_file(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $fs = get_file_storage();

        $draftid = file_get_unused_draft_itemid();
        $context = \context_user::instance($USER->id);

        $draft = new \stdClass();
        $draft->contextid = $context->id;
        $draft->component = 'user';
        $draft->filearea = 'draft';
        $draft->itemid = $draftid;
        $draft->filepath = '/';
        $draft->filename = 'image.png';

        $file = $fs->create_file_from_string($draft, 'Content is file');
        $document = json_encode(
            [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'attachments',
                        'content' => [
                            [
                                'type' => 'attachment',
                                'attrs' => [
                                    'url' => \moodle_url::make_draftfile_url(
                                        $draftid,
                                        $draft->filepath,
                                        $draft->filename
                                    )->out(),
                                    'filename' => $draft->filename,
                                    'size' => $file->get_filesize(),
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $formdata = new \stdClass();
        $formdata->summary_editor = [
            'format' => FORMAT_JSON_EDITOR,
            'text' => $document,
            'itemid' => $draftid
        ];

        // We want to assure that the $formdata will not be modified by the function.
        $cloned = fullclone($formdata);

        $updated = file_postupdate_standard_editor(
            $cloned,
            'summary',
            ['maxfiles' => 1],
            $context,
            'editor_weka',
            'default',
            15
        );

        // We are making sure that the file is being modified.
        $this->assertObjectHasAttribute('summary', $updated);
        $file = $fs->get_file($context->id, 'editor_weka', 'default', 15, '/', $draft->filename);

        $this->assertNotNull($file);
        $this->assertNotEquals(false, $file);
    }

    /**
     * @return void
     */
    public function test_processing_files(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();

        $context = \context_user::instance($USER->id);
        $fs = get_file_storage();

        $attachments = [];
        $document = [
            'type' => 'doc',
            'content' => [],
        ];

        $draftid = file_get_unused_draft_itemid();

        for ($i = 0; $i < 5; $i++) {
            $file = new \stdClass();
            $file->contextid = $context->id;
            $file->component = 'user';
            $file->filearea = 'draft';
            $file->itemid = $draftid;
            $file->filepath = '/';
            $file->filename = "file_{$i}.png";

            $instance = $fs->create_file_from_string($file, 'content in file');
            $attachments[] = [
                'type' => 'attachment',
                'attrs' => [
                    'url' => \moodle_url::make_draftfile_url(
                        $draftid,
                        $file->filepath,
                        $file->filename
                    )->out(),
                    'filename' => $file->filename,
                    'size' => $instance->get_filesize(),
                ]
            ];
        }

        $document['content'][] = [
            'type' => 'attachments',
            'content' => $attachments
        ];

        $formdata = new \stdClass();
        $formdata->summary_editor = [
            'format' => FORMAT_JSON_EDITOR,
            'text' => json_encode($document),
            'itemid' => $draftid
        ];

        // We want to assure that the $formdata will not be modified by the function.
        $cloned = fullclone($formdata);
        $updated = file_postupdate_standard_editor(
            $cloned,
            'summary',
            ['maxfiles' => -1],
            $context,
            'editor_weka',
            'default',
            15
        );

        $this->assertObjectHasAttribute('summary', $updated);

        $files = $fs->get_area_files($context->id, 'editor_weka', 'default', 15);
        $files = array_filter(
            $files,
            function (\stored_file $file): bool {
                return !$file->is_directory();
            }
        );

        $this->assertCount(5, $files);
    }
}