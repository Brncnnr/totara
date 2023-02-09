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
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

use totara_topic\topic;
use totara_engage\answer\answer_type;

class engage_survey_delete_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_topic_notify_survey_author(): void {
        $generator = $this->getDataGenerator();

        // Log in as admin and start creating a list of topics.
        $this->setAdminUser();
        $topics = [];

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');

        for ($i = 0; $i < 5; $i++) {
            $topic = $topic_generator->create_topic();
            $topics[] = $topic;
        }

        // Log in as different user and start creating a list of survey.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $survey_names = [];
        $topic_ids = array_map(
            function (topic $topic): int {
                return $topic->get_id();
            },
            $topics
        );

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        for ($i = 0; $i < 5; $i++) {
            $survey = $survey_generator->create_survey(
                null,
                [],
                answer_type::MULTI_CHOICE,
                ['topics' => $topic_ids]
            );

            $survey_names[] = $survey->get_name();
        }

        // Log in as admin and start deleting the topic(s).
        $this->setAdminUser();

        // Make sure the adhoc tasks are cleaned.
        $this->executeAdhocTasks();

        // Get message sinks.
        $message_sink = $this->redirectMessages();

        /** @var topic $topic */
        foreach ($topics as $topic) {
            $this->setAdminUser();
            $topic->delete();
            $this->executeAdhocTasks();

            $messages = $message_sink->get_messages();
            $this->assertNotEmpty($messages);
            $this->assertCount(1, $messages);

            $message = reset($messages);
            $this->assertIsObject($message);

            $this->assertObjectHasAttribute('fullmessage', $message);
            $this->assertObjectHasAttribute('fullmessagehtml', $message);

            foreach ($survey_names as $survey_name) {
                $this->assertStringContainsString($survey_name, $message->fullmessage);
                $this->assertStringContainsString($survey_name, $message->fullmessagehtml);
            }

            // Clear the sink
            $message_sink->clear();
        }
    }
}