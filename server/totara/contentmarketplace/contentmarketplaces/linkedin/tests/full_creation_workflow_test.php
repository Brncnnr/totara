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
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\dto\course_creation_result;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\totara_notification\resolver\import_course_partial_failure as import_course_partial_failure_resolver;
use contentmarketplace_linkedin\webapi\resolver\mutation\catalog_import_create_course;
use core\json_editor\helper\document_helper;
use core\notification;
use core\orm\query\builder;
use core\output\notification as output_notification;
use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\testing\helper;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\manager\event_queue_manager;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_full_creation_workflow_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        // We need to clear the adhoc tasks first, so that we can be sure it is on initial state
        // for the adhoc tasks.
        self::executeAdhocTasks();

        // Enable linkedin content marketplace.
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->enable();
    }

    /**
     * Steps:
     * + Create two categories
     * + Create a user
     * + Assign user as a course creation role of one category
     * + Create three learning objects
     * + Set the config for max_selected_learning_items
     * + Login as user
     * + Execute the graphql given mapped learning object with one to a authorized category and
     *   the rest to unauthorized categories.
     * + Check that there is an adhoc task queued
     * + Execute the adhoc tasks
     * + Check that there is only one course created
     * + Check that there is a queue of notification event
     * + Process the queue
     * + Check that user had received a message.
     *
     * @return void
     */
    public function test_create_courses_from_learning_objects_with_partial_failure(): void {
        $generator = self::getDataGenerator();
        $category_one = $generator->create_category();
        $category_two = $generator->create_category();

        $user = $generator->create_user();
        $context_category_one = context_coursecat::instance($category_one->id);
        $context_category_two = context_coursecat::instance($category_two->id);

        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category_one, $user->id));
        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category_two, $user->id));

        $creator_role = helper::get_course_creator_role();
        role_assign($creator_role, $user->id, $context_category_one->id);

        self::assertTrue(has_capability('totara/contentmarketplace:add', $context_category_one, $user->id));
        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category_two, $user->id));

        $marketplace_generator = generator::instance();
        $db = builder::get_db();

        $learning_object['one'] = $marketplace_generator->create_learning_object('urn:lyndaCourse:252');
        $learning_object['two'] = $marketplace_generator->create_learning_object('urn:lyndaCourse:324');
        $learning_object['three'] = $marketplace_generator->create_learning_object('urn:lyndaCourse:333');

        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));
        self::assertEquals(0, $db->count_records('task_adhoc'));

        self::assertEmpty(notification::fetch());

        self::setUser($user);

        // Set to 2 items so that we can pipe the process thru adhoc tasks.
        config::set_max_selected_items_number(2);

        /** @var course_creation_result $result */
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(catalog_import_create_course::class),
            [
                'input' => [
                    [
                        'learning_object_id' => $learning_object['one']->id,
                        'category_id' => $category_one->id
                    ],
                    [
                        'learning_object_id' => $learning_object['two']->id,
                        'category_id' => $category_two->id,
                    ],
                    [
                        'learning_object_id' => $learning_object['three']->id,
                        'category_id' => $category_two->id
                    ]
                ]
            ]
        );

        self::assertInstanceOf(course_creation_result::class, $result);
        self::assertTrue($result->is_successful());
        self::assertNotNull($result->get_redirect_url());
        self::assertEmpty($result->get_message());

        $notifications = notification::fetch();
        self::assertNotEmpty($notifications);
        self::assertCount(1, $notifications);

        /** @var output_notification $notification */
        [$notification] = $notifications;
        self::assertEquals(get_string('course_content_delay_creation', 'contentmarketplace_linkedin'), $notification->get_message());

        // No courses were created yet.
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

        // An adhoc task was queued.
        self::assertEquals(1, $db->count_records('task_adhoc', ['component' => 'contentmarketplace_linkedin']));
        self::executeAdhocTasks();

        // Adhoc task is executed, hence none will be kept.
        self::assertEquals(0, $db->count_records('task_adhoc', ['component' => 'contentmarketplace_linkedin']));

        // There should be one course created
        self::assertEquals(1, $db->count_records('course', ['containertype' => course::get_type()]));
        $exist_sql = '
            SELECT 1 FROM "ttr_course" c
            INNER JOIN "ttr_contentmarketplace" cm ON c.id = cm.course
            WHERE cm.learning_object_id = :id
            AND cm.learning_object_marketplace_component = \'contentmarketplace_linkedin\'
        ';

        self::assertFalse($db->record_exists_sql($exist_sql, ['id' => $learning_object['three']->id]));
        self::assertFalse($db->record_exists_sql($exist_sql, ['id' => $learning_object['two']->id]));
        self::assertTrue($db->record_exists_sql($exist_sql, ['id' => $learning_object['one']->id]));

        self::assertEquals(1, $db->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => import_course_partial_failure_resolver::class]));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEmpty($sink->get_messages());

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => import_course_partial_failure_resolver::class]));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        [$message] = $messages;
        self::assertIsObject($message);
        self::assertObjectHasAttribute('userto', $message);
        self::assertObjectHasAttribute('subject', $message);
        self::assertObjectHasAttribute('fullmessage', $message);
        self::assertObjectHasAttribute('fullmessagehtml', $message);

        self::assertIsObject($message->userto);
        self::assertObjectHasAttribute('id', $message->userto);
        self::assertEquals($user->id, $message->userto->id);

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
}
