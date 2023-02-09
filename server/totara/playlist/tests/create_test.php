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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_playlist\playlist;
use core\webapi\execution_context;
use totara_webapi\graphql;
use core\json_editor\node\mention;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use totara_engage\access\access;

class totara_playlist_create_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_playlist(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $playlist = playlist::create('Hello world');
        $this->assertTrue($DB->record_exists('playlist', ['id' => $playlist->get_id()]));

        $this->assertEquals('Hello world', $playlist->get_name());
    }

    /**
     * @return void
     */
    public function test_create_playlist_with_graphql(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $parameters = [
            'name' => 'Hello World',
            'summary' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text("This is just a summary")]
            ]),
            'summary_format' => FORMAT_JSON_EDITOR,
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_create_playlist');
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('playlist', $result->data);
        $playlist = $result->data['playlist'];

        $this->assertEquals('Hello World', $playlist['name']);
        $this->assertStringContainsString('This is just a summary', $playlist['summary']);
    }

    /**
     * @return void
     */
    public function test_create_playlist_with_mention(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Clear out the adhoc tasks.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        playlist::create(
            "Playlist 101",
            access::PRIVATE,
            null,
            $user_one->id,
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text("This playlist is dedicated for user "),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR
        );

        // Now run adhoc tasks which it should send an email out to user two.
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

    /**
     * @return void
     */
    public function test_create_playlist_with_mention_and_guest_user_in_session(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Clear out the adhoc tasks.
        $this->executeAdhocTasks();
        $message_sink = $this->redirectMessages();

        $guest_user = guest_user();
        $this->setUser($guest_user);

        playlist::create(
            "Playlist 101",
            access::PRIVATE,
            null,
            $user_one->id,
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [
                            text::create_json_node_from_text("This playlist is dedicated for user two: "),
                            mention::create_raw_node($user_two->id)
                        ]
                    ]
                ]
            ]),
            FORMAT_JSON_EDITOR
        );

        // Now run adhoc tasks which it should send an email out to user two.
        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);
        self::assertObjectHasAttribute('useridfrom', $message);
        self::assertObjectHasAttribute('useridto', $message);

        self::assertNotEquals($guest_user->id, $message->useridfrom);

        self::assertEquals($user_one->id, $message->useridfrom);
        self::assertEquals($user_two->id, $message->useridto);
    }

    /**
     * @return void
     */
    public function create_playlist_with_invalid_length(): void {
        $this->setAdminUser();

        $name = 'TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax';
        $this->assertEquals(76, strlen($name));

        $this->expectException(\totara_playlist\exception\playlist_exception::class);
        $this->expectExceptionMessage('Cannot create playlist');
        playlist::create($name);
    }

    /**
     * @return void
     */
    public function create_playlist_with_invalid_length_via_graphql(): void {
        $this->setAdminUser();
        $name = 'TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax';
        $this->assertEquals(76, strlen($name));

        $parameters = [
            'name' => $name,
            'summary' => "This is just a summary",
            'summary_format' => FORMAT_PLAIN,
        ];

        $ec = execution_context::create('ajax', 'totara_playlist_create_playlist');

        $this->expectException(\totara_playlist\exception\playlist_exception::class);
        $this->expectExceptionMessage('Cannot create playlist');
        graphql::execute_operation($ec, $parameters);
    }
}