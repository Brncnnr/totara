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

use editor_weka\local\media;
use editor_weka\webapi\resolver\query\draft_file;
use editor_weka\webapi\resolver\type\file;
use totara_webapi\phpunit\webapi_phpunit_helper;

class editor_weka_webapi_get_file_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");
    }

    /**
     * @return void
     */
    public function test_get_draft_file_query(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context = context_user::instance($user_one->id);

        $fs = get_file_storage();
        $draft_id = file_get_unused_draft_itemid();

        $file_record = new stdClass();
        $file_record->contextid = $context->id;
        $file_record->itemid = $draft_id;
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filepath = '/';
        $file_record->filename = 'boom.png';

        $fs->create_file_from_string($file_record, 'big citi boi - binz');

        $fetched_file = $this->resolve_graphql_query(
            $this->get_graphql_name(draft_file::class),
            [
                'item_id' => $draft_id,
                'filename' => 'boom.png'
            ]
        );

        self::assertInstanceOf(stored_file::class, $fetched_file);

        // Check on the resolving type.
        self::assertEquals(
            'boom.png',
            $this->resolve_graphql_type(
                $this->get_graphql_name(file::class),
                'filename',
                $fetched_file
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_draft_file_via_persist_operation(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context = context_user::instance($user_one->id);

        $fs = get_file_storage();
        $draft_id = file_get_unused_draft_itemid();

        $file_record = new stdClass();
        $file_record->contextid = $context->id;
        $file_record->itemid = $draft_id;
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filepath = '/';
        $file_record->filename = 'boom.png';

        $stored_file = $fs->create_file_from_string($file_record, 'big citi boi - binz');
        $result = $this->execute_graphql_operation(
            'editor_weka_get_draft_file',
            [
                'item_id' => $draft_id,
                'filename' => 'boom.png'
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('file', $result->data);
        self::assertNotEmpty($result->data['file']);

        $file_data = $result->data['file'];
        self::assertArrayHasKey('filename', $file_data);
        self::assertEquals('boom.png', $file_data['filename']);

        self::assertArrayHasKey('file_size', $file_data);
        self::assertEquals($stored_file->get_filesize(), $file_data['file_size']);

        self::assertArrayHasKey('item_id', $file_data);
        self::assertEquals($draft_id, $file_data['item_id']);

        self::assertArrayHasKey('mime_type', $file_data);
        self::assertEquals($stored_file->get_mimetype(), $file_data['mime_type']);

        self::assertArrayHasKey('url', $file_data);
        self::assertArrayHasKey('download_url', $file_data);

        self::assertArrayHasKey('media_type', $file_data);
        self::assertEquals(media::TYPE_IMAGE, $file_data['media_type']);
    }
}