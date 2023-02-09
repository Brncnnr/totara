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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use totara_topic\topic;

final class engage_article_delete_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_topic_notify_author(): void {
        $generator = $this->getDataGenerator();

        // Log in as admin and start creating the topics.
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topics = [];

        for ($i = 0; $i < 5; $i++) {
            $topic = $topic_generator->create_topic();
            $topics[] = $topic;
        }

        // Now log in as different user and start creating several articles that use the 5 of topics above
        // to find out if there is notification sent.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $topic_ids = array_map(
            function (topic $topic): int {
                return $topic->get_id();
            },
            $topics
        );

        $article_names = [];
        for ($i = 0; $i < 5; $i++) {
            $article = $article_generator->create_article(['topics' => $topic_ids]);
            $article_names[] = $article->get_name(true);
        }

        // Now start deleting the topic() - which it has to be admin as an actor.
        $this->setAdminUser();

        // Make sure that all the adhoc tasks are cleared prior to this.
        $this->executeAdhocTasks();

        // Start the sink
        $message_sink = $this->redirectMessages();

        /** @var topic $topic */
        foreach ($topics as $topic) {
            $this->setAdminUser();
            $topic->delete();
            $this->executeAdhocTasks();

            // There should be only one messages send out to the author of the article - which
            // had been using the topics prior to this point.
            $messages = $message_sink->get_messages();
            $this->assertCount(1, $messages);

            $message = reset($messages);
            $this->assertIsObject($message);
            $this->assertObjectHasAttribute('fullmessage', $message);
            $this->assertObjectHasAttribute('fullmessagehtml', $message);

            foreach ($article_names as $article_name) {
                $this->assertStringContainsString($article_name, $message->fullmessage);
                $this->assertStringContainsString($article_name, $message->fullmessagehtml);
            }

            // Clear messages for next loop
            $message_sink->clear();
        }
    }
}