<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_notification
 */

use core_user\totara_notification\placeholder\user;
use totara_core\extended_context;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_queue as notification_queue_entity;
use totara_notification\model\notification_preference as notification_preference_model;
use totara_notification\task\process_notification_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use core_phpunit\testcase;

class totara_notification_notification_recipient_testcase extends testcase {
    /**
     * @return void
     */
    public function test_mock_recipient(): void {
        $notification_generator = $this->get_generator();
        $notification_generator->include_mock_notifiable_event_resolver();

        // Create notification preference with a mock recipient that will resolve
        // to user one's ID.
        $notification_preference = $this->create_notification_preference(
            mock_resolver::class,
            totara_notification_mock_recipient::class,
        );

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $context_user = context_user::instance($user_two->id);

        $notification_generator->add_string_subject_to_mock_built_in_notification('Recipient');
        $notification_generator->add_string_body_to_mock_built_in_notification('Test');

        // Lets add user one as the recipient of the email.
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        // Adding queue to process.
        $queue = new notification_queue_entity();
        $queue->set_extended_context(extended_context::make_with_context($context_user));
        $queue->scheduled_time = 15;
        $queue->event_data = json_encode([
            'message' => 'my_name',
            'expected_context_id' => context_system::instance()->id,
        ]);
        $queue->notification_preference_id = $notification_preference->get_id();
        $queue->save();

        // Start the message redirection.
        $sink = $this->redirectMessages();
        $task = new process_notification_queue_task();
        $this->assertDebuggingCalled("process_notification_queue_task has been deprecated since 17.0");

        $notification_generator->set_due_time_of_process_notification_task($task, 50);
        $task->execute();
        $messages = $sink->get_messages();

        $this->assertCount(1, $messages);
        $this->assertEquals($user_one->id, $messages[0]->userto->id);
    }

    /**
     * @return void
     */
    public function test_inherit_from_system_recipient_instead_of_category_level_at_course_level(): void {
        $generator = self::getDataGenerator();

        /** @var notification_generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();
        $notification_generator->include_mock_owner();

        $builder = notification_preference_builder::from_exist_model($system_built_in);
        $builder->set_recipients([totara_notification_mock_owner::class]);
        $builder->save();

        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);
        $context_category = context_coursecat::instance($course->category);

        // Create the category level which we are overriding the body only - NOTE NO RECIPIENT
        $category_built_in = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_category),
            [
                'body' => 'Category body',
            ]
        );

        $course_built_in = $notification_generator->create_overridden_notification_preference(
            $category_built_in,
            extended_context::make_with_context($context_course),
            [
                'subject' => 'Subject',
                'subject_format' => FORMAT_PLAIN
            ]
        );

        // Default recipient class to mock built in notification.
        self::assertNotEquals([totara_notification_mock_recipient::class], $course_built_in->get_recipients());

        // This is expectation to fallback to, because we override the built-in notification at system context.
        self::assertEquals([totara_notification_mock_owner::class], $course_built_in->get_recipients());
    }

    /**
     * @return notification_generator
     */
    private function get_generator(): notification_generator {
        return notification_generator::instance();
    }

    /**
     * @param string $resolver_class
     * @param string $recipient_class
     *
     * @return notification_preference_model
     */
    private function create_notification_preference(string $resolver_class,
                                                    string $recipient_class): notification_preference_model {
        return $this->get_generator()->create_notification_preference(
            $resolver_class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => "Notification preference body for [{$resolver_class},{$recipient_class}]",
                'subject' => 'Notification preference subject',
                'body_format' => FORMAT_MOODLE,
                'title' => 'Notification preference title',
                'recipient' => $recipient_class
            ]
        );
    }
}
