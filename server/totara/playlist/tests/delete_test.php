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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_engage\resource\resource_factory;
use totara_playlist\playlist;

class totara_playlist_delete_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_playlist(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        $playlist = playlist::create('Hello world');
        $resouce_item = resource_factory::create_instance_from_id($article->get_id());
        $playlist->add_resource($resouce_item);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $gen->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            $playlist::get_resource_type(),
            'comment'
        );

        $reply = $comment_generator->create_reply($comment->get_id(), 'this is reply');

        $id = $comment->get_id();
        $reply_id = $reply->get_id();

        $params = ['id' => $playlist->get_id()];

        $sql = 'SELECT 1 FROM "ttr_playlist" p WHERE p.id = :id';
        $this->assertTrue($DB->record_exists_sql($sql, $params));
        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $id]));
        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $reply_id]));

        $record = $DB->get_record('engage_resource', ['id' => $resouce_item->get_id()]);
        $this->assertEquals(1, $record->countusage);

        $playlist->delete();

        $record = $DB->get_record('engage_resource', ['id' => $resouce_item->get_id()]);
        $this->assertEquals(0, $record->countusage);
        $this->assertFalse($DB->record_exists_sql($sql, $params));
        $this->assertFalse($DB->record_exists('totara_comment', ['id' => $id]));
        $this->assertFalse($DB->record_exists('totara_comment', ['id' => $reply_id]));
    }

    /**
     * @return void
     */
    public function test_delete_playlist_by_admin(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();

        $this->setUser($user);
        $playlist = playlist::create('Hello world');

        $this->assertTrue($DB->record_exists('playlist', ['id' => $playlist->get_id()]));
        $this->assertEquals((int)$user->id, $playlist->get_userid());

        $id = $playlist->get_id();

        // Login as admin.
        $this->setAdminUser();
        $playlist->delete();
        $this->assertFalse($DB->record_exists('playlist', ['id' => $id]));
    }
}