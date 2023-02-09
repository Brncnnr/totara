<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package message_email
 */

global $CFG;
require_once($CFG->dirroot . '/message/output/email/message_output_email.php');

use core_phpunit\testcase;


class message_output_email_test extends testcase {

    /**
     * Only testing the attachment list parsing.
     * Actual sending of the attachments is tested in totara_core_moodlelib_testcase
     */
    public function test_get_attachment_list() {
        global $CFG;

        $CFG->allowattachments = 1;
        $eventdata = new stdClass();

        // No attachment
        static::assertEmpty(message_output_email::get_attachment_list($eventdata));

        // Only name, no file
        $eventdata->id = 123;
        $eventdata->attachname = 'test.txt';
        static::assertEmpty(message_output_email::get_attachment_list($eventdata));

        // One in list
        $eventdata->attachment_list = [
            'test1.txt' => static::get_file('test1.txt'),
        ];
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEquals(['test1.txt'], array_keys($list));

        // One legacy
        $eventdata->attachment_list = [];
        $eventdata->attachment = static::get_file('test1.txt');
        $eventdata->attachname = 'test1.txt';
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEquals(['test1.txt'], array_keys($list));

        // One in legacy, one in list
        $eventdata->attachment_list = [
            'test2.txt' => static::get_file('test2.txt'),
        ];
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEqualsCanonicalizing(['test1.txt', 'test2.txt'], array_keys($list));

        // Same one in legacy and in list
        $eventdata->attachment_list['test1.txt'] = static::get_file('test1.txt');
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEqualsCanonicalizing(['test1.txt', 'test2.txt'], array_keys($list));

        // Global adding of attachments disabled
        $CFG->allowattachments = 0;
        $eventdata->attachment_list = [
            'test1.txt' => static::get_file('test1.txt'),
        ];
        static::assertEmpty(message_output_email::get_attachment_list($eventdata));
    }

    public function test_get_attachment_list_legacy_no_name() {
        $eventdata = new stdClass();
        $eventdata->attachment = static::get_file('test1.txt');
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEmpty($list);
        static::assertDebuggingCalled('Attachments should have a file name. Some attachments are not sent.');
    }

    public function test_get_attachment_list_legacy_not_file() {
        $eventdata = new stdClass();
        $eventdata->attachment = 'whatever';
        $eventdata->attachname = 'file.name';
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEmpty($list);
        static::assertDebuggingCalled('Attachments should be of type stored_file. Attachment file.name not sent.');
    }

    public function test_get_attachment_list_some_no_name() {
        $eventdata = new stdClass();
        $eventdata->attachment_list = [static::get_file('test1.txt')];
        $eventdata->attachment_list['test1.txt'] = static::get_file('test1.txt');
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEquals(['test1.txt'], array_keys($list));
        static::assertDebuggingCalled('Attachments should have a file name. Some attachments are not sent.');
    }

    public function test_get_attachment_list_some_not_file() {
        $eventdata = new stdClass();
        $eventdata->attachment = 'whatever';
        $eventdata->attachname = 'file.name';
        $eventdata->attachment_list = [
            'test1.txt' => static::get_file('test1.txt'),
            'another.file' => 'file.list',
        ];
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEquals(['test1.txt'], array_keys($list));
        static::assertDebuggingCalled([
            'Attachments should be of type stored_file. Attachment another.file not sent.',
            'Attachments should be of type stored_file. Attachment file.name not sent.',
        ]);
    }

    public function test_get_attachment_list_no_name() {
        $eventdata = new stdClass();
        $eventdata->attachment = static::get_file('test1.txt');
        $list = message_output_email::get_attachment_list($eventdata);
        static::assertEmpty($list);
        static::assertDebuggingCalled('Attachments should have a file name. Some attachments are not sent.');
    }

    private static function get_file(string $filename) {
        $fs = get_file_storage();
        $syscontext = context_system::instance();
        $component = 'message';
        $filearea = 'unittest';
        $itemid = 0;
        $filepath = '/';

        if ($fs->file_exists($syscontext->id, $component, $filearea, $itemid, $filepath, $filename)) {
            $file = $fs->get_file($syscontext->id, $component, $filearea, $itemid, $filepath, $filename);
        } else {
            $filerecord = array(
                'contextid' => $syscontext->id,
                'component' => $component,
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => $filepath,
                'filename' => $filename,
            );

            $file = $fs->create_file_from_string($filerecord, 'Test ical content');
        }

        return $file;
    }

}
