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
 * @package totara_topic
 */
defined('MOODLE_INTERNAL') || die();

use core\task\manager;
use core_tag\entity\tag_instance;
use totara_engage\access\access;
use totara_topic\task\delete_notify_task;

/**
 * This test is here to make sure that if anyone changes the behaviour
 * of the adhoc task will have to modify this test as well.
 *
 * Just a reminder to keep that person to take account of regression :)
 */
class totara_topic_delete_notify_task_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/totara/topic/tests/fixtures/topic_resolver.php');

        parent::setUpBeforeClass();
    }

    /**
     * @return void
     */
    public function test_execute_with_no_actor_data(): void {
        $task = new delete_notify_task();
        $task->set_custom_data([
            'topicvalue' => 'd',
            'components' => [],
        ]);

        $task->execute();
        $debug_messages = $this->getDebuggingMessages();

        $this->assertCount(1, $debug_messages);

        $debug_message = reset($debug_messages);
        $this->assertSame(
            "The custom data for the task does not have key 'actor'",
            $debug_message->message
        );

        $this->resetDebugging();
    }

    /**
     * @return void
     */
    public function test_execute_with_no_topic_value(): void {
        $task = new delete_notify_task();
        $task->set_custom_data([
            'actor' => 42,
            'components' => [],
        ]);

        $task->execute();
        $debug_messages = $this->getDebuggingMessages();

        $this->assertCount(1, $debug_messages);

        $debug_message = reset($debug_messages);
        $this->assertSame(
            "The custom data for the task does not have key 'topicvalue'",
            $debug_message->message
        );

        $this->resetDebugging();
    }

    /**
     * @return void
     */
    public function test_execute_with_no_components_data(): void {
        $task = new delete_notify_task();
        $task->set_custom_data([
            'actor' => 42,
            'topicvalue' => 'd'
        ]);

        $task->execute();
        $debug_messages = $this->getDebuggingMessages();

        $this->assertCount(1, $debug_messages);

        $debug_message = reset($debug_messages);
        $this->assertSame(
            "The custom data for the task does not have key 'components'",
            $debug_message->message
        );

        $this->resetDebugging();
    }

    public function test_recipients_language_setting_is_observed(): void {
        $generator = self::getDataGenerator();
        $fake_language = 'xo_ox';
        $this->add_fake_language_pack(
            $fake_language,
            [
                'totara_topic' => [
                    'topicdeleted' => 'Fake language subject string'
                ]
            ]
        );

        $actor = $generator->create_user();
        $user_one = $generator->create_user(['lang' => $fake_language]);
        $user_two = $generator->create_user();

        // Clear adhoc tasks.
        $this->executeAdhocTasks();
        self::setAdminUser();

        // Create a topic.
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = self::getDataGenerator()->get_plugin_generator('totara_topic');
        $topic_generator->add_default_area();
        $topic = $topic_generator->create_topic();

        // Create an article for both users.
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        foreach ([$user_one, $user_two] as $user) {
            self::setUser($user);
            $article_generator->create_article([
                'userid' => $user->id,
                'access' => access::PUBLIC,
                'name' => 'Test article',
                'topics' => [$topic->get_id()],
            ]);
        }

        // Build components metadata.
        $components['engage_article']['engage_resource'] = [];
        $repo = tag_instance::repository();
        $instances = $repo->get_instances_of_tag($topic->get_id());
        foreach ($instances as $instance) {
            $components['engage_article']['engage_resource'][] = $instance->itemid;
        }

        // Create and queue task.
        $task = new delete_notify_task();
        $task->set_component('totara_topic');
        $task->set_custom_data(
            [
                'actor' => $actor->id,
                'topicvalue' => 'Topic test name',
                'components' => $components
            ]
        );
        manager::queue_adhoc_task($task);

        // Start the sink and execute the adhoc tasks.
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $message_sink->get_messages();
        self::assertCount(2, $messages);
        $message_data = [];
        foreach ($messages as $message) {
            $message_data[$message->useridto] = $message->subject;
        }

        // user_one should receive message in fake language.
        self::assertEquals('Fake language subject string', $message_data[$user_one->id]);
        // user_two should receive message in default (en).
        self::assertEquals('A topic has been deleted', $message_data[$user_two->id]);
    }
}