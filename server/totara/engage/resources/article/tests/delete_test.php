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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use engage_article\totara_engage\resource\article;
use totara_engage\access\access;
use totara_engage\exception\resource_exception;
use totara_engage\timeview\time_view;
use totara_reaction\reaction_helper;
use core\json_editor\node\image;

class engage_article_delete_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_article(): void {
        global $DB, $CFG, $USER;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);

        $doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' =>  'This is an article'
                        ]
                    ],
                ]
            ]
        ];

        $resource = article::create(
            [
                'format' => FORMAT_JSON_EDITOR,
                'content' => json_encode($doc),
                'timeview' => time_view::LESS_THAN_FIVE,
                'draft_id' => 25,
                'name' => 'Is this random enuf ?'
            ],
            $USER->id
        );

        // Creating a draft file.
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $record = new \stdClass();
        $record->contextid = $resource->get_context_id();
        $record->component = 'user';
        $record->filearea = 'draft';
        $record->itemid = 42;
        $record->filename = 'admin.png';
        $record->userid = $USER->id;
        $record->filepath = '/';

        $file = $fs->create_file_from_string($record, 'hello world');
        $doc['content'][] = image::create_raw_node_from_image($file);

        $resource->update([
            'content' => json_encode($doc),
            'draft_id' => 42,
            'access' => access::PUBLIC
        ]);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $resource->get_id(),
            $resource::get_resource_type(),
            'comment'
        );

        $reply = $comment_generator->create_reply($comment->get_id());
        $user_two = $generator->create_user();

        // Create like for an article, as a second user
        $reaction = reaction_helper::create_reaction(
            $resource->get_id(),
            $resource::get_resource_type(),
            'media',
            $user_two->id
        );

        // Create like for an comment and reply as a second user
        $reaction_comment = reaction_helper::create_reaction(
            $comment->get_id(),
            'totara_comment',
            $comment->get_comment_area(),
            $user_two->id
        );

        $reaction_reply = reaction_helper::create_reaction(
            $reply->get_id(),
            'totara_comment',
            $reply->get_comment_area(),
            $user_two->id
        );

        $sql = '
            SELECT 1 FROM "ttr_engage_article" ea
            INNER JOIN "ttr_engage_resource" er ON er.instanceid  = ea.id AND er.resourcetype = :type
            WHERE ea.id = :articleid
        ';

        $params = [
            'type' => article::get_resource_type(),
            'articleid' => $resource->get_instanceid()
        ];

        $fs = get_file_storage();

        $id = $comment->get_id();
        $reply_id = $reply->get_id();
        $reaction_id = $reaction->get_id();
        $reaction_comment_id = $reaction_comment->get_id();
        $reaction_reply_id = $reaction_reply->get_id();
        $context_id = $resource->get_context_id();
        $resource_id = $resource->get_id();

        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $id]));
        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $reply_id]));
        $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction_id]));
        $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction_comment_id]));
        $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction_reply_id]));
        $this->assertTrue($DB->record_exists_sql($sql, $params));
        $this->assertNotNull($fs->get_file($context_id, 'enegage_article', 'image', $resource_id, '/', 'admin.png'));

        $resource->delete();

        $this->assertFalse($fs->get_file($context_id, 'enegage_article', 'image', $resource_id, '/', 'admin.png'));
        $this->assertFalse($DB->record_exists_sql($sql, $params));
        $this->assertFalse($DB->record_exists('totara_comment', ['id' => $id]));
        $this->assertFalse($DB->record_exists('totara_comment', ['id' => $reply_id]));
        $this->assertFalse($DB->record_exists('reaction', ['id' => $reaction_id]));
        $this->assertFalse($DB->record_exists('reaction', ['id' => $reaction_comment_id]));
        $this->assertFalse($DB->record_exists('reaction', ['id' => $reaction_reply_id]));
    }

    /**
     * @return void
     */
    public function test_delete_article_without_permissions(): void {
        global $DB;
        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $this->setUser($user);

        $data = [
            'name' => 'hello world',
            'content' => "nmjij ipfj wqp kfqwop fkqpwo jkfpoqw jfpoqw",
            'timeview' => time_view::LESS_THAN_FIVE

        ];

        $resource = article::create($data);

        $user2 = $gen->create_user();
        $this->setUser($user2);

        $exception = null;

        try {
            $resource->delete($user2->id);
        } catch (resource_exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertEquals(get_string('error:delete', 'engage_article'), $exception->getMessage());

        $sql = '
            SELECT 1 FROM "ttr_engage_article" ea
            INNER JOIN "ttr_engage_resource" er ON er.instanceid = ea.id AND er.resourcetype = :type
            WHERE ea.id = :articleid
        ';

        $params = [
            'type' => article::get_resource_type(),
            'articleid' => $resource->get_instanceid()
        ];

        $this->assertTrue($DB->record_exists_sql($sql, $params));
    }
}