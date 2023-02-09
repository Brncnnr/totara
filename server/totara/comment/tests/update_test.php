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
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\node\attachments;
use core\json_editor\node\paragraph;
use core\webapi\execution_context;
use totara_comment\comment_helper;
use totara_webapi\graphql;

/**
 * Tests to check if user is able to update comments/replies or not.
 */
class totara_comment_update_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_comment_via_graphql(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            42,
            'dota_windrunner',
            'arrow_shot',
            'A wind of change is blowing.'
        );

        $this->assertTrue(
            $DB->record_exists('totara_comment', ['id' => $comment->get_id()])
        );

        $content = json_encode([
            'type' => 'doc',
            'content' => [paragraph::create_json_node_from_text('The markswoman of the wood.')]
        ]);

        // Add context that is different from system context.
        $comment_generator->add_context_for_default_resolver(context_user::instance($user->id));

        $ec = execution_context::create('ajax', 'totara_comment_update_comment');
        $result = graphql::execute_operation(
            $ec,
            [
                'id' => $comment->get_id(),
                'content' => $content,
                'format' => FORMAT_JSON_EDITOR
            ]
        );

        $this->assertEmpty($result->errors);

        // Check if the comment has actually been updated.
        $record = $DB->get_record('totara_comment', ['id' => $comment->get_id()]);
        $this->assertEquals($content, $record->content);
    }

    /**
     * @return void
     */
    public function test_update_comment_with_file(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        // Create a comment with empty content.
        $document = [
            'type' => 'doc',
            'content' => []
        ];

        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment',
            42,
            json_encode($document),
            FORMAT_JSON_EDITOR
        );

        $this->assertEmpty($comment->get_content_text());

        // Now update the content with files.
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $draft_id = file_get_unused_draft_itemid();

        $context_user = context_user::instance($user_one->id);
        $fs = get_file_storage();
        $files = [];

        for ($i = 0; $i < 5; $i++) {
            $file_record = new stdClass();
            $file_record->itemid = $draft_id;
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->filepath = '/';
            $file_record->filename = uniqid() . '.png';
            $file_record->contextid = $context_user->id;

            $files[] = $fs->create_file_from_string($file_record, 'File content ' . $i);
        }

        $document['content'][] = paragraph::create_json_node_from_text("Check out the list of attachments");
        $document['content'][] = attachments::create_raw_node_from_list($files);

        $document_json = json_encode($document);
        $updated_comment = comment_helper::update_content($comment->get_id(), $document_json, $draft_id);

        $this->assertEquals($updated_comment->get_id(), $comment->get_id());

        $content_text = content_to_text($document_json, FORMAT_JSON_EDITOR);
        $content_text = file_rewrite_urls_to_pluginfile($content_text, $draft_id);

        $this->assertEquals($content_text, $updated_comment->get_content_text());
    }

    /**
     * Test whether a deleted comment can still be updated
     *
     * @return void
     */
    public function test_update_deleted_comment_via_graphql(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            22,
            'test_comment',
            'test_area',
            'Comment 1'
        );
        $comment->soft_delete();

        $this->assertTrue(
            $DB->record_exists('totara_comment', ['id' => $comment->get_id()])
        );

        $content =  json_encode([
            'type' => 'doc',
            'content' => [paragraph::create_json_node_from_text('Updated Comment')]
        ]);

        $comment_generator->add_context_for_default_resolver(context_user::instance($user->id));

        $ec = execution_context::create('ajax', 'totara_comment_update_comment');
        $result = graphql::execute_operation(
            $ec,
            [
                'id' => $comment->get_id(),
                'content' => $content,
                'format' => FORMAT_JSON_EDITOR
            ]
        );

        $this->assertNotEmpty($result->errors);
        $this->assertIsArray($result->errors);

        // Message will be held in the topmost error element
        $error = current($result->errors);
        $error_string = get_string('error:update', 'totara_comment');
        $this->assertEquals($error_string, $error->getMessage());

        // Check if the comment has not been updated.
        $record = $DB->get_record('totara_comment', ['id' => $comment->get_id()]);
        $this->assertNotEquals($content, $record->content);
        $this->assertEmpty($record->content);
    }

    /**
     * Test whether a deleted reply can still be updated
     *
     * @return void
     */
    public function test_update_deleted_reply_via_graphql(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            22,
            'test_comment',
            'test_area',
            'Comment 1'
        );
        $reply = $comment_generator->create_reply(
            $comment->get_id(),
            'Reply 1'
        );
        $reply->soft_delete();

        $this->assertTrue(
            $DB->record_exists('totara_comment', ['id' => $reply->get_id()])
        );

        $comment_generator->add_context_for_default_resolver(context_user::instance($user->id));
        $content = json_encode([
            'type' => 'doc',
            'content' => [paragraph::create_json_node_from_text('Updated Reply')]
        ]);

        $ec = execution_context::create('ajax', 'totara_comment_update_comment');
        $result = graphql::execute_operation(
            $ec,
            [
                'id' => $reply->get_id(),
                'content' => $content,
                'format' => FORMAT_JSON_EDITOR
            ]
        );

        $this->assertNotEmpty($result->errors);
        $this->assertIsArray($result->errors);

        // Message will be held in the topmost error element
        $error = current($result->errors);
        $error_string = get_string('error:update', 'totara_comment');
        $this->assertEquals($error_string, $error->getMessage());

        // Check if the comment has not been updated.
        $record = $DB->get_record('totara_comment', ['id' => $reply->get_id()]);
        $this->assertNotEquals($content, $record->content);
        $this->assertEmpty($record->content);
    }
}