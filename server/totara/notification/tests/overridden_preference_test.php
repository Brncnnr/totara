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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use container_course\course;
use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\builder\notification_preference_builder;
use totara_notification\loader\notification_preference_loader;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

class totara_notification_overridden_preference_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_inherit_of_system(): void {
        $notification_generator = generator::instance();

        $notification_generator->include_mock_recipient();
        $notification_generator->include_mock_owner();
        $notification_generator->add_mock_built_in_notification_for_component();

        $notification_generator->add_string_subject_to_mock_built_in_notification("This is subject");
        $notification_generator->add_string_body_to_mock_built_in_notification("This is body");

        $mock_built_in = notification_preference_loader::get_built_in(totara_notification_mock_built_in_notification::class);
        self::assertEquals("This is subject", $mock_built_in->get_subject());
        self::assertEquals("This is body", $mock_built_in->get_body());
        self::assertEquals([totara_notification_mock_recipient::class], $mock_built_in->get_recipients());

        self::assertNotEquals('Subject 2', $mock_built_in->get_subject());
        self::assertNotEquals('Body 2', $mock_built_in->get_body());
        self::assertNotEquals([totara_notification_mock_owner::class], $mock_built_in->get_recipients());

        // Update the built in to make it custom value.
        $builder = notification_preference_builder::from_exist($mock_built_in->get_id());
        $builder->set_subject('Subject 2');
        $builder->set_body('Body 2');
        $builder->set_recipients([totara_notification_mock_owner::class]);

        $builder->save();
        $mock_built_in->refresh();

        self::assertNotEquals("This is subject", $mock_built_in->get_subject());
        self::assertNotEquals("This is body", $mock_built_in->get_body());
        self::assertNotEquals([totara_notification_mock_recipient::class], $mock_built_in->get_recipients());

        self::assertEquals('Subject 2', $mock_built_in->get_subject());
        self::assertEquals('Body 2', $mock_built_in->get_body());
        self::assertEquals([totara_notification_mock_owner::class], $mock_built_in->get_recipients());
    }

    /**
     * @return void
     */
    public function test_overridden_from_custom_with_two_level(): void {
        global $DB;

        $notification_generator = generator::instance();
        $notification_generator->include_mock_notifiable_event();
        $notification_generator->include_mock_recipient();
        $notification_generator->include_mock_owner();

        // Create the top level custom.
        $first_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'System body',
                'subject' => 'System subject',
                'body_format' => FORMAT_PLAIN,
                'subject_format' => FORMAT_PLAIN,
                'title' => 'System custom notification',
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        // Create the category level custom from top level.
        $category_id = $DB->get_field('course_categories', 'id', ['issystem' => 0], MUST_EXIST);
        $extended_context_category = extended_context::make_with_context(context_coursecat::instance($category_id));

        $second_custom = $notification_generator->create_overridden_notification_preference(
            $first_custom,
            $extended_context_category
        );

        $second_custom->refresh();

        self::assertEquals('System body', $second_custom->get_body());
        self::assertEquals('System subject', $second_custom->get_subject());
        self::assertEquals('System custom notification', $second_custom->get_title());
        self::assertEquals(FORMAT_PLAIN, $second_custom->get_body_format());
        self::assertEquals(FORMAT_PLAIN, $second_custom->get_subject_format());
        self::assertEquals([totara_notification_mock_recipient::class], $second_custom->get_recipients());

        // Create the overridden values.
        $second_custom_builder = notification_preference_builder::from_exist($second_custom->get_id());
        $second_custom_builder->set_body('Category body');
        $second_custom_builder->set_title('Category title');
        $second_custom_builder->set_subject('Category subject');
        $second_custom_builder->set_body_format(FORMAT_MOODLE);
        $second_custom_builder->set_subject_format(FORMAT_MOODLE);
        $second_custom_builder->set_recipients([totara_notification_mock_owner::class]);

        $second_custom_builder->save();
        $second_custom->refresh();

        self::assertNotEquals('System body', $second_custom->get_body());
        self::assertNotEquals('System subject', $second_custom->get_subject());
        self::assertNotEquals('System custom notification', $second_custom->get_title());
        self::assertNotEquals(FORMAT_PLAIN, $second_custom->get_body_format());
        self::assertNotEquals([totara_notification_mock_recipient::class], $second_custom->get_recipients());

        self::assertEquals('Category body', $second_custom->get_body());
        self::assertEquals('Category title', $second_custom->get_title());
        self::assertEquals('Category subject', $second_custom->get_subject());
        self::assertEquals(FORMAT_MOODLE, $second_custom->get_body_format());
        self::assertEquals(FORMAT_MOODLE, $second_custom->get_subject_format());
        self::assertEquals([totara_notification_mock_owner::class], $second_custom->get_recipients());
    }

    /**
     * @return void
     */
    public function test_overridden_from_built_in_with_three_level(): void {
        $generator = self::getDataGenerator();
        $notification_generator = generator::instance();

        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();
        $notification_generator->include_mock_recipient();
        $notification_generator->include_mock_owner();

        // Generate a course so that we can override the course and category level of this system built in
        // notification preference.
        $course_record = $generator->create_course();
        $course = course::from_record($course_record);

        // Create the category level which we are overriding the body only
        $extended_context_category = extended_context::make_with_context(context_coursecat::instance($course->category));
        $category_built_in = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            $extended_context_category,
            [
                'body' => 'Category body',
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        // Create the coruse level notification preference which we are overriding
        // the title only.
        $extended_context_course = extended_context::make_with_context(context_course::instance($course->id));
        $course_built_in = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            $extended_context_course,
            [
                'subject' => 'Course subject',
                'recipient' => totara_notification_mock_owner::class,
                'recipients' => [totara_notification_mock_owner::class],
            ]
        );

        // The body format and the subject are not overridden in any level.
        // Hence the last level course context should get these value from the system level.
        self::assertEquals($system_built_in->get_body_format(), $course_built_in->get_body_format());
        self::assertEquals($system_built_in->get_title(), $course_built_in->get_title());
        self::assertEquals([totara_notification_mock_owner::class], $course_built_in->get_recipients());

        // System check with the course.
        self::assertNotEquals($system_built_in->get_body(), $course_built_in->get_body());
        self::assertNotEquals($system_built_in->get_subject(), $course_built_in->get_subject());
        self::assertEquals([totara_notification_mock_recipient::class], $system_built_in->get_recipients());

        // Category check with the course.
        self::assertEquals($category_built_in->get_body_format(), $course_built_in->get_body_format());
        self::assertEquals($category_built_in->get_title(), $course_built_in->get_title());
        self::assertEquals($category_built_in->get_body(), $course_built_in->get_body());
        self::assertNotEquals($category_built_in->get_recipients(), $course_built_in->get_recipients());
        self::assertNotEquals($category_built_in->get_subject(), $course_built_in->get_subject());

        // System check with the category
        self::assertEquals($system_built_in->get_body_format(), $category_built_in->get_body_format());
        self::assertEquals($system_built_in->get_subject(), $category_built_in->get_subject());
        self::assertEquals($system_built_in->get_title(), $category_built_in->get_title());
        self::assertEquals($system_built_in->get_recipients(), $category_built_in->get_recipients());
        self::assertNotEquals($system_built_in->get_body(), $category_built_in->get_body());
    }
}