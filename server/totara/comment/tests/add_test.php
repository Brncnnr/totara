<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_comment\comment_helper;
use core\json_editor\node\image;
use core\json_editor\node\paragraph;

class totara_comment_add_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_comment(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment_view',
            42,
            'Hello world'
        );

        $this->assertStringContainsString('Hello world', $comment->get_content());
        $this->assertEquals('totara_comment', $comment->get_component());
        $this->assertEquals('comment_view', $comment->get_area());
        $this->assertEquals(42, $comment->get_instanceid());
    }

    /**
     * @return void
     */
    public function test_add_reply(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment_view',
            42,
            'hello world'
        );

        $reply = comment_helper::create_reply($comment->get_id(), 'this is reply');

        $this->assertEquals($reply->get_parent_id(), $comment->get_id());
        $this->assertEquals('this is reply', $reply->get_content());
        $this->assertEquals(42, $reply->get_instanceid());
        $this->assertTrue($reply->is_reply());
        $this->assertEquals(FORMAT_MOODLE, $reply->get_format());
    }

    /**
     * @return void
     */
    public function test_create_comment_with_files(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $context_user = \context_user::instance($user_one->id);

        // Create files before adding comments.
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $document = [
            'type' => 'doc',
            'content' => []
        ];

        for ($i = 0; $i < 5; $i++) {
            $file_record = new stdClass();
            $file_record->filename = uniqid() . ".png";
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->contextid = $context_user->id;
            $file_record->filepath = '/';
            $file_record->itemid = $draft_id;

            $file = $fs->create_file_from_string($file_record, "This is file '{$i}'");
            $document['content'][] = paragraph::create_json_node_from_text('This the file you are looking for');
            $document['content'][] = image::create_raw_node_from_image($file);
        }

        $document_json = json_encode($document);
        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment',
            42,
            $document_json,
            FORMAT_JSON_EDITOR,
            $draft_id
        );

        $this->assertNotEmpty($comment->get_id());
        $this->assertEquals($user_one->id, $comment->get_userid());

        // Rewritten content.
        $content_text = content_to_text($document_json, FORMAT_JSON_EDITOR);
        $content_text = file_rewrite_urls_to_pluginfile($content_text, $draft_id);

        $this->assertEquals($content_text, $comment->get_content_text());
    }

    /**
     * @return void
     */
    public function test_create_reply_with_file(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(42, 'totara_comment', 'comment');

        // Create a reply for the comment. However, we will need to create files first.
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $document = [
            'type' => 'doc',
            'content' => []
        ];

        $context_user = \context_user::instance($user_one->id);
        for ($i = 0; $i < 5; $i++) {
            $file_record = new stdClass();
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->itemid = $draft_id;
            $file_record->filename = uniqid() . '.png';
            $file_record->filepath = '/';
            $file_record->contextid = $context_user->id;

            $stored_file = $fs->create_file_from_string($file_record, "This is the file");
            $document['content'][] = paragraph::create_json_node_from_text("This is the file you are looking for");
            $document['content'][] = image::create_raw_node_from_image($stored_file);
        }

        $document_json = json_encode($document);
        $reply = comment_helper::create_reply(
            $comment->get_id(),
            $document_json,
            $draft_id,
            FORMAT_JSON_EDITOR,
            $user_one->id
        );

        $this->assertNotEmpty($reply->get_id());

        // Check if the content text is format correctly.
        $content_text = content_to_text($document_json, FORMAT_JSON_EDITOR);
        $content_text = file_rewrite_urls_to_pluginfile($content_text, $draft_id);

        $this->assertEquals($content_text, $reply->get_content_text());
    }
}