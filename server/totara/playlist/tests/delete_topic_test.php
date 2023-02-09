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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_topic\topic;

class totara_playlist_delete_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_topic_notify_playlist_owner(): void {
        $generator = $this->getDataGenerator();

        // Login as admin and start creating list of topics.
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topics = [];

        for ($i = 0; $i < 5; $i++) {
            $topics[] = $topic_generator->create_topic();
        }

        // Login as different user and start creating a list of playlists.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist_names = [];

        $topic_ids = array_map(
            function (topic $topic): int {
                return $topic->get_id();
            },
            $topics
        );

        for ($i = 0; $i < 5; $i++) {
            $playlist = $playlist_generator->create_playlist([
                'topics' => $topic_ids
            ]);

            $playlist_names[] = $playlist->get_name();
        }

        // Log in as admin and start deleting the topics.
        $this->setAdminUser();

        // Clear all the adhoc tasks prior to the actual assertion.
        $this->executeAdhocTasks();

        // Start the sink.
        $message_sink = $this->redirectMessages();

        /** @var topic $topic */
        foreach ($topics as $topic) {
            $this->setAdminUser();
            $topic->delete();
            $this->executeAdhocTasks();

            $messages = $message_sink->get_messages();
            $this->assertCount(1, $messages);

            $message = reset($messages);
            $this->assertIsObject($message);
            $this->assertObjectHasAttribute('fullmessage', $message);
            $this->assertObjectHasAttribute('fullmessagehtml', $message);

            foreach ($playlist_names as $playlist_name) {
                $this->assertStringContainsString($playlist_name, $message->fullmessage);
                $this->assertStringContainsString($playlist_name, $message->fullmessagehtml);
            }

            $message_sink->clear();
        }
    }
}