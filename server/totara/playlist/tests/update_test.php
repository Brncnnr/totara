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

use totara_playlist\playlist;
use totara_engage\access\access;
use totara_playlist\exception\playlist_exception;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core\json_editor\node\mention;

class totara_playlist_update_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_playlist(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();

        // Login as owner.
        $this->setUser($user);
        $playlist = playlist::create('Hello world');

        $this->assertTrue($DB->record_exists('playlist', ['id' => $playlist->get_id()]));
        $this->assertEquals((int)$user->id, $playlist->get_userid());

        $playlist->update('change by owner');
        $this->assertEquals('change by owner', $playlist->get_name());

        // Login as admin.
        $this->setAdminUser();
        $playlist->update('change by admin');
        $this->assertEquals('change by admin', $playlist->get_name());
    }

    /**
     * @return void
     */
    public function test_update_playlist_access_yeild_error(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['access' => access::PUBLIC]);

        $this->assertTrue($playlist->is_public());

        // Now try to update the playlist to private access.
        $this->expectException(playlist_exception::class);
        $playlist->update(null, access::PRIVATE);
    }

    /**
     * @return void
     */
    public function test_update_playlist_summary(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['summaryformat' => FORMAT_PLAIN]);

        $this->assertEquals(FORMAT_PLAIN, $playlist->get_summaryformat());
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text("This is empty summary")
            ]
        ]);

        $playlist->update(
            null,
            null,
            $document,
            FORMAT_JSON_EDITOR
        );

        $this->assertEquals(FORMAT_JSON_EDITOR, $playlist->get_summaryformat());
        $this->assertEquals($document, $playlist->get_summary());
    }

    /**
     * @return void
     */
    public function test_update_playlist_summary_with_mention(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Createthe playlist for user one.
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['userid' => $user_one->id]);

        self::assertEquals($user_one->id, $playlist->get_userid());
        $user_two = $generator->create_user();

        // Clear adhoc tasks.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $playlist->update(
            null,
            null,
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text('This playlist is dedicated for user '),
                            mention::create_raw_node($user_two->id)
                        ],
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR,
            $user_one->id
        );

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);
    }
}