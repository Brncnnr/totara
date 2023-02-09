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

use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference;
use totara_notification\testing\generator;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;
use totara_notification_mock_recipient as mock_recipient;

class totara_notification_notification_preference_builder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $notification_generator = generator::instance();
        $notification_generator->include_mock_scheduled_aware_notifiable_event_resolver();
        $notification_generator->include_mock_built_in_notification();
        $notification_generator->include_mock_recipient();
    }

    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_no_required_field_body(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_subject('subject');
        $builder->set_subject_format(FORMAT_PLAIN);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'body'");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_no_required_field_title(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_body('body');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_subject('subject');
        $builder->set_subject_format(FORMAT_PLAIN);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'title'");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_no_required_field_subject(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body('body');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_subject_format(FORMAT_PLAIN);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'subject'");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_no_required_field_body_format(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body('body');
        $builder->set_subject('subject');
        $builder->set_subject_format(FORMAT_PLAIN);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'body_format'");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_no_required_field_subject_format(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body('body');
        $builder->set_subject('subject');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'subject_format'");

        $builder->save();
    }


    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_no_required_field_enabled(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_subject('Subject');
        $builder->set_title('title');
        $builder->set_body('body');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_subject_format(FORMAT_PLAIN);
        $builder->set_recipients([mock_recipient::class]);
        $builder->set_schedule_offset(0);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'enabled'");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_create_custom_notification_preference(): void {
        global $DB;

        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body('body');
        $builder->set_subject('subject');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_subject_format(FORMAT_PLAIN);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);
        $builder->set_recipients([totara_notification_mock_recipient::class]);

        $preference = $builder->save();
        self::assertTrue($DB->record_exists(notification_preference::TABLE, ['id' => $preference->get_id()]));
    }

    /**
     * @return void
     */
    public function test_create_built_in_notification_preference(): void {
        global $DB;

        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_notification_class_name(totara_notification_mock_built_in_notification::class);
        $preference = $builder->save();

        self::assertTrue($DB->record_exists(notification_preference::TABLE, ['id' => $preference->get_id()]));
    }

    /**
     * @return void
     */
    public function test_create_overridden_notification_at_system_context(): void {
        $built_in_builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $built_in_builder->set_notification_class_name(totara_notification_mock_built_in_notification::class);
        $built_in_builder->set_ancestor_id(4242);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The ancestor's id should not be set when the context is in system");

        $built_in_builder->save();
    }

    /**
     * @return void
     */
    public function test_update_notification(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $first_custom = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'First body',
                'subject' => 'First subject',
                'body_format' => FORMAT_PLAIN,
                'title' => 'First title',
                'schedule_offset' => 0,
                'subject_format' => FORMAT_PLAIN,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        self::assertEquals('First body', $first_custom->get_body());
        self::assertEquals('First subject', $first_custom->get_subject());
        self::assertEquals('First title', $first_custom->get_title());
        self::assertEquals(0, $first_custom->get_schedule_offset());

        $builder = notification_preference_builder::from_exist($first_custom->get_id());
        $builder->set_body('Second body');
        $builder->set_subject('Second subject');
        $builder->set_title('Second title');
        $builder->set_schedule_offset(5);

        $builder->save();
        $first_custom->refresh();

        self::assertNotEquals('First body', $first_custom->get_body());
        self::assertNotEquals('First subject', $first_custom->get_subject());
        self::assertNotEquals('First title', $first_custom->get_title());
        self::assertNotEquals(0, $first_custom->get_schedule_offset());

        self::assertEquals('Second body', $first_custom->get_body());
        self::assertEquals('Second subject', $first_custom->get_subject());
        self::assertEquals('Second title', $first_custom->get_title());
        self::assertEquals(5, $first_custom->get_schedule_offset());
    }

    /**
     * @return void
     */
    public function test_update_notification_from_invalid_record(): void {
        $this->expectException(record_not_found_exception::class);
        notification_preference_builder::from_exist(4242);
    }

    /**
     * @return void
     */
    public function test_set_ancestor_id_of_updating_builder(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $custom = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'body',
                'subject' => 'subject',
                'body_format' => FORMAT_PLAIN,
                'title' => 'title',
                'schedule_offset' => 0,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        $builder = notification_preference_builder::from_exist($custom->get_id());
        $builder->set_ancestor_id(4242);

        $this->assertDebuggingCalled(
            "Do not set the ancestor's id of notification preference when updating a record"
        );
    }

    /**
     * @return void
     */
    public function test_reset_title_of_custom_notification_that_does_not_have_parent(): void {
        global $DB;

        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $notification_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'title' => 'This is title',
                'schedule_offset' => 0,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        self::assertTrue(
            $DB->record_exists(notification_preference::TABLE, ['id' => $notification_preference->get_id()])
        );

        // Start the update with the builder.
        $builder = notification_preference_builder::from_exist_model($notification_preference);
        $builder->set_title(null);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot reset the field 'title' for custom notification that does not have parent(s)");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_reset_body_of_custom_notification_that_does_not_have_parent(): void {
        global $DB;

        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $notification_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'title' => 'This is title',
                'schedule_offset' => 0,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        self::assertTrue(
            $DB->record_exists(notification_preference::TABLE, ['id' => $notification_preference->get_id()])
        );

        // Start the update with the builder.
        $builder = notification_preference_builder::from_exist_model($notification_preference);
        $builder->set_body(null);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot reset the field 'body' for custom notification that does not have parent(s)");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_reset_body_format_of_custom_notification_that_does_not_have_parent(): void {
        global $DB;

        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $notification_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'title' => 'This is title',
                'schedule_offset' => 0,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        self::assertTrue(
            $DB->record_exists(notification_preference::TABLE, ['id' => $notification_preference->get_id()])
        );

        // Start the update with the builder.
        $builder = notification_preference_builder::from_exist_model($notification_preference);
        $builder->set_body_format(null);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot reset the field 'body_format' for custom notification that does not have parent(s)");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_reset_subject_of_custom_notification_that_does_not_have_parent(): void {
        global $DB;

        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $notification_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'title' => 'This is title',
                'schedule_offset' => 0,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        self::assertTrue(
            $DB->record_exists(notification_preference::TABLE, ['id' => $notification_preference->get_id()])
        );

        // Start the update with the builder.
        $builder = notification_preference_builder::from_exist_model($notification_preference);
        $builder->set_subject(null);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot reset the field 'subject' for custom notification that does not have parent(s)");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_reset_schedule_offset_of_custom_notification_that_does_not_have_parent(): void {
        global $DB;

        $generator = generator::instance();
        $notification_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'title' => 'This is title',
                'schedule_offset' => 0,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        self::assertTrue(
            $DB->record_exists(notification_preference::TABLE, ['id' => $notification_preference->get_id()])
        );

        // Start the update with the builder.
        $builder = notification_preference_builder::from_exist_model($notification_preference);
        $builder->set_schedule_offset(null);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Cannot reset the field 'schedule_offset' for custom notification that does not have parent(s)"
        );

        $builder->save();
    }

    /**
     * Note that at a lower level of the system, we allow the title to be overridden.
     * However at the graphql layer, we will not allow to do so.
     *
     * @return void
     */
    public function test_override_built_in_notification_with_fields(): void {
        $generator = generator::instance();
        $system_built_in = $generator->add_mock_built_in_notification_for_component();

        self::assertNotEquals('This is new title', $system_built_in->get_title());
        self::assertNotEquals('This is a new body', $system_built_in->get_body());

        $builder = notification_preference_builder::from_exist_model($system_built_in);
        $builder->set_title('This is new title');
        $builder->set_body('This is a new body');

        // We do not save the overridden title.
        $builder->save();
        $system_built_in->refresh();

        self::assertEquals('This is new title', $system_built_in->get_title());
        self::assertEquals('This is a new body', $system_built_in->get_body());
    }

    /**
     * @return void
     */
    public function test_create_overridden_of_different_context(): void {
        $generator = self::getDataGenerator();
        $other_category = $generator->create_category();

        $misc_course = $generator->create_course();
        self::assertNotEquals($other_category->id, $misc_course->category);

        // Create a custom notification at course category context.
        $context_other_cat = context_coursecat::instance($other_category->id);
        $context_course = context_course::instance($misc_course->id);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $custom_category = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_other_cat),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        // Now start create an overridden of the custom category, but at a different context.
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context($context_course)
        );

        // Override the custom category.
        $builder->set_ancestor_id($custom_category->get_id());
        $builder->set_body('This is new body');
        $builder->set_subject('This is new subject');
        $builder->set_title('This new custom title');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "The context path of ancestor does not appear in the context path of the overridden preference"
        );

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_empty_required_field_body(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_body('');
        $builder->set_title('title');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_subject('subject');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'body'");

        $builder->save();
    }


    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_empty_required_field_title(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('');
        $builder->set_body('body');
        $builder->set_body_format(FORMAT_PLAIN);
        $builder->set_subject('subject');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'title'");

        $builder->save();
    }

    /**
     * @return void
     */
    public function test_create_of_custom_notification_with_empty_required_field_subject(): void {
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_subject('');
        $builder->set_title('title');
        $builder->set_body('body');
        $builder->set_body_format(FORMAT_PLAIN);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("When creating a new record the following field is required: 'subject'");

        $builder->save();
    }

    public function test_update_custom_notification_null_recipients_with_parent(): void {
        $course_generator = self::getDataGenerator();
        $generator = generator::instance();
        $notification_preference_parent = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'title' => 'This is title',
                'schedule_offset' => 0,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );
        $misc_course = $course_generator->create_course();
        $context_course = context_course::instance($misc_course->id);
        $notification_preference_child = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course),
            [
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'schedule_offset' => 0,
                'ancestor_id' => $notification_preference_parent->get_id(),
                'recipients' => [\totara_notification\recipient\manager::class],
            ]
        );

        $this->assertSame([\totara_notification\recipient\manager::class], $notification_preference_child->get_recipients());
    }
}