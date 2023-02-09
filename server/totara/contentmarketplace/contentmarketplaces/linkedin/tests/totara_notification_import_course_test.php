<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use container_course\course;
use contentmarketplace_linkedin\task\create_course_delay_task;
use contentmarketplace_linkedin\totara_notification\resolver\import_course_partial_failure as import_course_partial_failure_resolver;
use core\json_editor\helper\document_helper;
use core\orm\query\builder;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use totara_contentmarketplace\testing\helper;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\manager\event_queue_manager;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_totara_notification_import_course_testcase extends testcase {
    /**
     * The user's actor
     * @var stdClass|null
     */
    private $actor;

    /**
     * Set up the actor within this test suite.
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();
        $this->actor = $generator->create_user();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->actor = null;
    }

    /**
     * @return void
     */
    public function test_send_fully_success_imported_courses_notification(): void {
        // Steps:
        //      + Create a course category
        //      + Create a user
        //      + Assign the role to the user in the course category context.
        //      + Create a learning object
        //      + Log in as user
        //      + Queue an adhoc task for delay create courses out of the learning object
        //      + Execute the adhoc task
        //      + Check that there are no notifications in queue
        $generator = self::getDataGenerator();
        $course_category = $generator->create_category();

        $context_category = context_coursecat::instance($course_category->id);
        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category, $this->actor->id));

        role_assign(
            helper::get_course_creator_role(),
            $this->actor->id,
            $context_category->id
        );

        self::assertTrue(has_capability('totara/contentmarketplace:add', $context_category, $this->actor->id));

        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('urn:lyndaCourse:252');

        self::setUser($this->actor);
        $task = create_course_delay_task::enqueue(
            [
                [
                    'learning_object_id' => $learning_object->id,
                    'category_id' => $course_category->id
                ]
            ]
        );

        // Check that there are no queued notification for now.
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        // Check that zero courses before tasks executed.
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

        // Execute the tasks.
        $task->execute();
        self::assertEquals(0,
            $db->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => import_course_partial_failure_resolver::class])
        );
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        // Checks that the courses are created
        self::assertEquals(1, $db->count_records('course', ['containertype' => course::get_type()]));
    }

    /**
     * @return void
     */
    public function test_send_partial_failure_imported_courses_notification(): void {
        // Steps:
        //      + Created two categories
        //      + Created a user
        //      + Assigned user to one of the category as course creator
        //      + Create three learning objects
        //      + Queue adhoc task with learning objects that one is mapped to authorized category and the other two
        //        learning objects should be mapped to unauthorized
        //      + Execute the task
        //      + Check that there are queue items for notification
        //      + Check that there are only one course created
        //      + Process the queues
        //      + Check that email is sent to the user
        $generator = self::getDataGenerator();
        $category_one = $generator->create_category();
        $category_two = $generator->create_category();

        $context_category_one = context_coursecat::instance($category_one->id);
        $context_category_two = context_coursecat::instance($category_two->id);

        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category_one, $this->actor->id));
        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category_two, $this->actor->id));

        role_assign(
            helper::get_course_creator_role(),
            $this->actor->id,
            $context_category_one->id
        );

        self::assertTrue(has_capability('totara/contentmarketplace:add', $context_category_one, $this->actor->id));
        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category_two, $this->actor->id));

        $marketplace_generator = generator::instance();
        $learning_object['one'] = $marketplace_generator->create_learning_object('urn:lyndaCourse:252');
        $learning_object['two'] = $marketplace_generator->create_learning_object('urn:lyndaCourse:253');
        $learning_object['three'] = $marketplace_generator->create_learning_object('urn:lyndaCourse:254');

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));
        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        self::setUser($this->actor);
        $task = create_course_delay_task::enqueue(
            [
                [
                    'learning_object_id' => $learning_object['one']->id,
                    'category_id' => $category_one->id
                ],
                [
                    'learning_object_id' => $learning_object['two']->id,
                    'category_id' => $category_two->id
                ],
                [
                    'learning_object_id' => $learning_object['three']->id,
                    'category_id' => $category_two->id
                ]
            ]
        );

        $task->execute();

        // There should only be one course created
        self::assertEquals(1, $db->count_records('course', ['containertype' => course::get_type()]));
        $exist_sql = '
            SELECT 1 FROM "ttr_course" c
            INNER JOIN "ttr_contentmarketplace" cm ON c.id = cm.course
            WHERE cm.learning_object_id = :learning_object_id
            AND cm.learning_object_marketplace_component = \'contentmarketplace_linkedin\'
        ';

        self::assertFalse($db->record_exists_sql($exist_sql, ['learning_object_id' => $learning_object['three']->id]));
        self::assertFalse($db->record_exists_sql($exist_sql, ['learning_object_id' => $learning_object['two']->id]));
        self::assertTrue($db->record_exists_sql($exist_sql, ['learning_object_id' => $learning_object['one']->id]));

        self::assertEquals(1,
            $db->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => import_course_partial_failure_resolver::class])
        );
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEmpty($sink->get_messages());
        self::assertEquals(0, $sink->count());

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);
        self::assertEquals(1, $sink->count());

        $message = reset($messages);
        self::assertIsObject($message);
        self::assertObjectHasAttribute('userto', $message);
        self::assertObjectHasAttribute('subject', $message);
        self::assertObjectHasAttribute('fullmessage', $message);
        self::assertObjectHasAttribute('fullmessagehtml', $message);

        self::assertIsObject($message->userto);
        self::assertObjectHasAttribute('id', $message->userto);
        self::assertEquals($this->actor->id, $message->userto->id);

        self::assertEquals(
            get_string('import_course_partial_failure_subject', 'contentmarketplace_linkedin'),
            $message->subject
        );

        // Construct a body for assertion
        $body = get_string('import_course_partial_failure_body', 'contentmarketplace_linkedin');

        // Convert to json content first into json then into html document.
        // As this is the process from C.N API.
        $body = format_text(
            document_helper::create_json_string_document_from_text($body),
            FORMAT_JSON_EDITOR
        );

        // Then replace on the placeholder.
        $titles = [$learning_object['two']->title, $learning_object['three']->title];
        core_collator::asort($titles, core_collator::SORT_NATURAL);

        $titles = implode(
            '<br/>',
            array_map(
                function (string $title): string {
                    return get_string('learning_object_title_list_item', 'contentmarketplace_linkedin', $title);
                },
                $titles
            )
        );
        $body = str_replace('[learning_objects:titles_list]', $titles, $body);

        $body = str_replace(
            '[learning_objects:catalog_import_link]',
            sprintf(
                '<a href="%s">%s</a>',
                (new moodle_url("/totara/contentmarketplace/explorer.php?marketplace=linkedin"))->out(false),
                get_string('catalog_title', 'contentmarketplace_linkedin')
            ),
            $body
        );

        self::assertEquals(html_to_text($body, 0), $message->fullmessage);
        self::assertEquals($body, $message->fullmessagehtml);
    }

    /**
     * @return void
     */
    public function test_send_full_failure_imported_courses_notification(): void {
        // Steps:
        //      + Created one category
        //      + Created a user
        //      + Create three learning objects
        //      + Queue adhoc task with learning objects that mapped to the category that user is not authorized.
        //      + Execute the task
        //      + Check that there are queue items for notification
        //      + Check that there are no courses created
        //      + Process the queues
        //      + Check that email is sent to the user
        $generator = self::getDataGenerator();
        $category = $generator->create_category();
        $context_category = context_coursecat::instance($category->id);

        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category, $this->actor->id));

        $marketplace_generator = generator::instance();
        $learning_object_1 = $marketplace_generator->create_learning_object('urn:lyndaCourse:252');
        $learning_object_2 = $marketplace_generator->create_learning_object('urn:lyndaCourse:253');

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));
        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        self::setUser($this->actor);
        $task = create_course_delay_task::enqueue(
            [
                [
                    'learning_object_id' => $learning_object_1->id,
                    'category_id' => $category->id
                ],
                [
                    'learning_object_id' => $learning_object_2->id,
                    'category_id' => $category->id
                ],
            ]
        );

        $task->execute();

        // There should only be one course created
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

        self::assertEquals(0, $db->count_records(notification_queue::TABLE));
        self::assertEquals(1, $db->count_records(notifiable_event_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $db->count_records(notification_queue::TABLE));
        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertEquals(1, $sink->count());
        self::assertCount(1, $messages);

        $message = reset($messages);
        self::assertIsObject($message);
        self::assertObjectHasAttribute('userto', $message);
        self::assertObjectHasAttribute('subject', $message);
        self::assertObjectHasAttribute('fullmessage', $message);
        self::assertObjectHasAttribute('fullmessagehtml', $message);

        self::assertIsObject($message->userto);
        self::assertObjectHasAttribute('id', $message->userto);
        self::assertEquals($this->actor->id, $message->userto->id);
        self::assertEquals(
            get_string('import_course_full_failure_subject', 'contentmarketplace_linkedin'),
            $message->subject
        );

        $body = get_string('import_course_full_failure_body', 'contentmarketplace_linkedin');
        $body = format_text(
            document_helper::create_json_string_document_from_text($body),
            FORMAT_JSON_EDITOR
        );

        $body = str_replace(
            "[catalog_import:page_link_placeholder]",
            sprintf(
                '<a href="%s">%s</a>',
                new moodle_url("/totara/contentmarketplace/explorer.php?marketplace=linkedin"),
                get_string('catalog_title', 'contentmarketplace_linkedin')
            ),
            $body
        );

        self::assertEquals(html_to_text($body, 0), $message->fullmessage);
        self::assertEquals($body, $message->fullmessagehtml);
    }
}
