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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\testing\generator as notification_generator;
use totara_notification\recipient\subject;
use core\orm\query\builder;
use totara_notification\model\notification_preference;
use totara_notification\external_helper;

/**
 * @group totara_notification
 */
class totara_notification_external_helper_testcase extends testcase {
    /**
     * @var notification_preference
     */
    protected $notif_system;

    /**
     * @var notification_preference
     */
    protected $notif_category;

    /**
     * @var notification_preference
     */
    protected $notif_course_natural;

    /**
     * @var notification_preference
     */
    protected $notif_course_extended;

    /**
     * @return void
     */
    public function test_remove_notification_preference_on_system_context(): void {
        // Verify the setup.
        $db = builder::get_db();
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(4, $records);

        // Run the function.
        external_helper::remove_notification_preferences(
            $this->notif_system->get_extended_context()->get_context_id(),
            $this->notif_system->get_extended_context()->get_component(),
            $this->notif_system->get_extended_context()->get_area(),
            $this->notif_system->get_extended_context()->get_item_id()
        );

        // Check that all four records were deleted.
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(0, $records);
    }

    /**
     * @return void
     */
    public function test_remove_notification_preference_on_category_context(): void {
        // Verify the setup.
        $db = builder::get_db();
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(4, $records);

        // Run the function.
        external_helper::remove_notification_preferences(
            $this->notif_category->get_extended_context()->get_context_id(),
            $this->notif_category->get_extended_context()->get_component(),
            $this->notif_category->get_extended_context()->get_area(),
            $this->notif_category->get_extended_context()->get_item_id()
        );

        // Check that three records were deleted, leaving just one.
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(1, $records);

        // Make sure the correct record is remaining.
        self::assertTrue(
            $db->record_exists(
                'notification_preference',
                [
                    'id' => $this->notif_system->get_id()
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_remove_notification_preference_on_course_context(): void {
        // Verify the setup.
        $db = builder::get_db();
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(4, $records);

        // Run the function.
        external_helper::remove_notification_preferences(
            $this->notif_course_natural->get_extended_context()->get_context_id(),
            $this->notif_course_natural->get_extended_context()->get_component(),
            $this->notif_course_natural->get_extended_context()->get_area(),
            $this->notif_course_natural->get_extended_context()->get_item_id()
        );

        // Check that two records were deleted, leaving two.
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(2, $records);

        // Make sure the correct records are remaining.
        self::assertTrue(
            $db->record_exists(
                'notification_preference',
                [
                    'id' => $this->notif_system->get_id()
                ]
            )
        );
        self::assertTrue(
            $db->record_exists(
                'notification_preference',
                [
                    'id' => $this->notif_category->get_id()
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_remove_notification_preference_with_component_area_and_item_id(): void {
        // Verify the setup.
        $db = builder::get_db();
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(4, $records);

        // Run the function.
        external_helper::remove_notification_preferences(
            $this->notif_course_extended->get_extended_context()->get_context_id(),
            $this->notif_course_extended->get_extended_context()->get_component(),
            $this->notif_course_extended->get_extended_context()->get_area(),
            $this->notif_course_extended->get_extended_context()->get_item_id()
        );

        // Check that one record was deleted, leaving three.
        $records = $db->get_records('notification_preference', ['resolver_class_name' => totara_notification_mock_scheduled_aware_event_resolver::class]);
        self::assertCount(3, $records);

        // Make sure the correct records are remaining.
        self::assertTrue(
            $db->record_exists(
                'notification_preference',
                [
                    'id' => $this->notif_system->get_id()
                ]
            )
        );
        self::assertTrue(
            $db->record_exists(
                'notification_preference',
                [
                    'id' => $this->notif_category->get_id()
                ]
            )
        );
        self::assertTrue(
            $db->record_exists(
                'notification_preference',
                [
                    'id' => $this->notif_course_natural->get_id()
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function setUp(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $notification_generator = notification_generator::instance();
        $notification_generator->include_mock_scheduled_aware_notifiable_event_resolver();

        // Create a custom notification in system context.
        $this->notif_system = $notification_generator->create_notification_preference(
            totara_notification_mock_scheduled_aware_event_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => 'test body',
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        // Override in cat context.
        $this->notif_category = $notification_generator->create_notification_preference(
            totara_notification_mock_scheduled_aware_event_resolver::class,
            extended_context::make_with_context(
                context_coursecat::instance(1)
            ),
            [
                'ancestor_id' => $this->notif_system->get_id(),
                'schedule_offset' => 111,
                'recipient' => subject::class,
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => 'test override body',
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        // Override in natural course context.
        $this->notif_course_natural = $notification_generator->create_notification_preference(
            totara_notification_mock_scheduled_aware_event_resolver::class,
            extended_context::make_with_context(
                context_course::instance($course->id)
            ),
            [
                'ancestor_id' => $this->notif_system->get_id(),
                'schedule_offset' => 222,
                'recipient' => subject::class,
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => 'test body',
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        // Override in an extended course context.
        $this->notif_course_extended = $notification_generator->create_notification_preference(
            totara_notification_mock_scheduled_aware_event_resolver::class,
            extended_context::make_with_context(
                context_course::instance($course->id),
                'core_course',
                'course',
                $course->id
            ),
            [
                'ancestor_id' => $this->notif_system->get_id(),
                'schedule_offset' => 333,
                'recipient' => subject::class,
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => 'test body',
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        $this->notif_system = null;
        $this->notif_category = null;
        $this->notif_course_natural = null;
        $this->notif_course_extended = null;
    }
}