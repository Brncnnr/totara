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

use core\orm\query\builder;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user;
use totara_core\extended_context;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference as entity;
use totara_notification\event\update_custom_notification_preference_event;
use totara_notification\event\update_overridden_notification_preference_event;
use totara_notification\exception\notification_exception;
use totara_notification\json_editor\node\placeholder;
use totara_notification\loader\notification_preference_loader;
use totara_notification\local\schedule_helper;
use totara_notification\model\notification_preference as model;
use totara_notification\placeholder\placeholder_option;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_on_event;
use totara_notification\testing\generator;
use totara_notification\webapi\resolver\mutation\update_notification_preference;
use totara_notification_mock_built_in_notification as mock_built_in;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_update_notification_preference_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        $notification_generator = generator::instance();

        $notification_generator->include_mock_scheduled_aware_notifiable_event_resolver();
        $notification_generator->add_mock_built_in_notification_for_component();
        $notification_generator->include_mock_recipient();
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_without_title(): void {
        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);

        self::assertNotEquals('Newly updated body', $system_built_in->get_body());
        self::assertNotEquals('Newly updated subject', $system_built_in->get_subject());

        $this->setAdminUser();

        /** @var model $preference */
        $preference = $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $system_built_in->get_id(),
                'body' => 'Newly updated body',
                'subject' => 'Newly updated subject',
            ]
        );

        $this->assertDebuggingCalled(
            [
                'Do not use update_notification_preference::get_middleware any more, use update_notification_preference_v2::get_middleware instead',
                'Do not use update_notification_preference::resolve any more, use update_notification_preference_v2::resolve instead',
            ]
        );

        self::assertInstanceOf(model::class, $preference);
        self::assertEquals($system_built_in->get_id(), $preference->get_id());

        $system_built_in->refresh();

        self::assertEquals('Newly updated body', $system_built_in->get_body());
        self::assertEquals('Newly updated subject', $system_built_in->get_subject());
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_of_built_in_with_title(): void {
        $this->setAdminUser();
        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        // Start updating the notification preference with title.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The title of overridden notification preference cannot be updated");

        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $system_built_in->get_id(),
                    'title' => 'Kaboom martin garrix',
                ]
            );
        } catch (\Exception $ex) {
            $this->resetDebugging();
            throw $ex;
        }
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_of_non_overridden_custom_with_title(): void {
        $this->setAdminUser();

        $generator = generator::instance();
        $custom_notification = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'title' => 'This is custom title',
                'body' => 'This is custom body',
                'subject' => 'This is custom subject',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertEquals('This is custom title', $custom_notification->get_title());
        self::assertEquals('This is custom body', $custom_notification->get_body());
        self::assertEquals('This is custom subject', $custom_notification->get_subject());
        self::assertEquals(FORMAT_MOODLE, $custom_notification->get_body_format());
        self::assertEquals(FORMAT_PLAIN, $custom_notification->get_subject_format());

        self::assertNotEquals('Updated title', $custom_notification->get_title());

        // Run mutation to update the custom notification.
        /** @var model $updated_notification */
        $updated_notification = $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $custom_notification->get_id(),
                'title' => 'Updated title',
            ]
        );
        $this->assertDebuggingCalledCount(2);

        self::assertInstanceOf(model::class, $updated_notification);
        self::assertEquals($custom_notification->get_id(), $updated_notification->get_id());

        $custom_notification->refresh();
        self::assertEquals('Updated title', $custom_notification->get_title());
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_of_overridden_custom_with_title(): void {
        $this->setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $notification_generator = generator::instance();
        $system_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'title' => 'This is custom title',
                'body' => 'This is custom body',
                'subject' => 'This is custom subject',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        // Note that the generator's api allow us to set the title.
        $course_custom = $notification_generator->create_overridden_notification_preference(
            $system_custom,
            extended_context::make_with_context(context_course::instance($course->id)),
            [
                'body' => 'course body',
                'subject' => 'course subject',
            ]
        );

        self::assertEquals('course body', $course_custom->get_body());
        self::assertEquals('course subject', $course_custom->get_subject());
        self::assertEquals(FORMAT_MOODLE, $course_custom->get_body_format());
        self::assertEquals('This is custom title', $course_custom->get_title());

        // Run mutation to update the custom notification.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The title of overridden notification preference cannot be updated");

        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $course_custom->get_id(),
                    'title' => 'Updated title',
                ]
            );
        } catch (\Exception $ex) {
            $this->resetDebugging();
            throw $ex;
        }
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_of_non_overridden_custom_with_reset_title(): void {
        $this->setAdminUser();

        $generator = generator::instance();
        $custom_notification = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'title' => 'This is custom title',
                'body' => 'This is custom body',
                'subject' => 'This is custom subject',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot reset the title of notification of custom notification that does not have parent");

        // Run mutation to update the custom notification.
        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $custom_notification->get_id(),
                    'title' => null,
                ]
            );
        } catch (\Exception $ex) {
            $this->resetDebugging();
            throw $ex;
        }
    }

    /**
     * @return void
     */
    public function test_reset_notification_preference_body_with_empty_string(): void {
        $generator = generator::instance();
        $generator->add_string_body_to_mock_built_in_notification('This is built-in body');

        $this->setAdminUser();
        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        self::assertEquals('This is built-in body', $system_built_in->get_body());

        $builder = notification_preference_builder::from_exist_model($system_built_in);
        $builder->set_body('This is overridden body');

        $builder->save();
        $system_built_in->refresh();

        self::assertNotEquals('This is built-in body', $system_built_in->get_body());
        self::assertEquals('This is overridden body', $system_built_in->get_body());

        // Run the mutation.
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $system_built_in->get_id(),
                'body' => null,
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $system_built_in->refresh();

        self::assertNotEquals('This is overridden body', $system_built_in->get_body());
        self::assertEquals('This is built-in body', $system_built_in->get_body());
    }

    /**
     * @return void
     */
    public function test_reset_notification_preference_subject_with_empty_string(): void {
        $generator = generator::instance();
        $generator->add_string_subject_to_mock_built_in_notification('This is built-in subject');

        $this->setAdminUser();
        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        self::assertEquals('This is built-in subject', $system_built_in->get_subject());

        $builder = notification_preference_builder::from_exist_model($system_built_in);
        $builder->set_subject('This is overridden subject');

        $builder->save();
        $system_built_in->refresh();

        self::assertNotEquals('This is built-in subject', $system_built_in->get_subject());
        self::assertEquals('This is overridden subject', $system_built_in->get_subject());

        // Run the mutation.
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $system_built_in->get_id(),
                'subject' => null,
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $system_built_in->refresh();

        self::assertNotEquals('This is overridden subject', $system_built_in->get_subject());
        self::assertEquals('This is built-in subject', $system_built_in->get_subject());
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_with_invalid_body_format(): void {
        $this->setAdminUser();
        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        // Run the mutation.
        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $system_built_in->get_id(),
                    'body_format' => 4242,
                ]
            );
        } catch (\Exception $ex) {
            $this->resetDebugging();
            throw $ex;
        }
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_with_invalid_subject_format(): void {
        $this->setAdminUser();
        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        // Run the mutation.
        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $system_built_in->get_id(),
                    'subject_format' => 4242,
                ]
            );
        } catch (\Exception $ex) {
            $this->resetDebugging();
            throw $ex;
        }
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_without_providing_fields(): void {
        $this->setAdminUser();
        $generator = generator::instance();

        $notification = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'Custom body',
                'body_format' => FORMAT_MOODLE,
                'subject' => 'Custom subject',
                'subject_format' => FORMAT_PLAIN,
                'title' => 'Custom title',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertEquals('Custom body', $notification->get_body());
        self::assertEquals('Custom subject', $notification->get_subject());
        self::assertEquals('Custom title', $notification->get_title());

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $notification->get_id(),
                'body' => 'updated body',
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $notification->refresh();

        self::assertNotEquals('Custom body', $notification->get_body());
        self::assertEquals('updated body', $notification->get_body());
        self::assertEquals('Custom subject', $notification->get_subject());
        self::assertEquals('Custom title', $notification->get_title());
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_for_schedule(): void {
        $this->setAdminUser();
        $generator = generator::instance();

        $notification = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body' => 'Custom body',
                'body_format' => FORMAT_MOODLE,
                'subject' => 'Custom subject',
                'title' => 'Custom title',
                'schedule_offset' => 0,
                'subject_format' => FORMAT_PLAIN,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertEquals('Custom body', $notification->get_body());
        self::assertEquals('Custom subject', $notification->get_subject());
        self::assertEquals('Custom title', $notification->get_title());
        self::assertEquals(0, $notification->get_schedule_offset());

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $notification->get_id(),
                'schedule_offset' => 10,
                'schedule_type' => schedule_after_event::identifier(),
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $notification->refresh();

        self::assertNotEquals(0, $notification->get_schedule_offset());
        self::assertEquals(schedule_helper::days_to_seconds(10), $notification->get_schedule_offset());

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $notification->get_id(),
                'schedule_offset' => 5,
                'schedule_type' => schedule_before_event::identifier(),
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $notification->refresh();

        self::assertNotEquals(schedule_helper::days_to_seconds(10), $notification->get_schedule_offset());
        self::assertEquals(schedule_helper::days_to_seconds(-5), $notification->get_schedule_offset());

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $notification->get_id(),
                'schedule_offset' => 0,
                'schedule_type' => schedule_on_event::identifier(),
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $notification->refresh();

        self::assertNotEquals(schedule_helper::days_to_seconds(-5), $notification->get_schedule_offset());
        self::assertEquals(0, $notification->get_schedule_offset());
    }

    /**
     * @return void
     */
    public function test_update_custom_notification_body_with_format_json_editor(): void {
        $generator = generator::instance();

        totara_notification_mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'user',
                user::class,
                $generator->give_my_mock_lang_string('User'),
                function (): void {
                    // The test is not about this function - so no point to write one.
                    throw new coding_exception("Do not call to this function in unit test");
                }
            )
        );

        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body_format' => FORMAT_PLAIN,
                'body' => 'This is body',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertEquals(FORMAT_PLAIN, $preference->get_body_format());
        self::assertEquals('This is body', $preference->get_body());

        $this->setAdminUser();
        $updated_body = document_helper::json_encode_document(
            document_helper::create_document_from_content_nodes([
                paragraph::create_json_node_with_content_nodes([
                    text::create_json_node_from_text('Hello '),
                    placeholder::create_node_from_key_and_label('user:firstname', 'User first name'),
                ]),
            ])
        );

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $preference->get_id(),
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => $updated_body,
            ]
        );
        $this->assertDebuggingCalledCount(2);

        // Refresh with the newly updated field
        $preference->refresh();
        self::assertNotEquals(FORMAT_PLAIN, $preference->get_body_format());
        self::assertEquals(FORMAT_JSON_EDITOR, $preference->get_body_format());

        self::assertNotEquals('This is body', $preference->get_body());
        self::assertEquals($updated_body, $preference->get_body());
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_with_valid_recipient(): void {
        $this->setAdminUser();
        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);

        // Run the mutation.
        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $system_built_in->get_id(),
                    'recipient' => totara_notification_mock_recipient::class,
                ]
            );
            $this->assertDebuggingCalledCount(2);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_with_invalid_recipient(): void {
        $this->setAdminUser();
        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);

        // Run the mutation.
        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $system_built_in->get_id(),
                    'recipient' => 'totara_non\\existent\\recipient\\class',
                ]
            );
            $this->fail('Exception is expected but not thrown');
        } catch (Exception $e) {
            $this->assertDebuggingCalledCount(2);
            self::assertEquals(
                $e->getMessage(),
                'Coding error detected, it must be fixed by a programmer: ' .
                'totara_non\existent\recipient\class is not a predefined recipient class'
            );
        }
    }

    /**
     * @return void
     */
    public function test_user_cannot_update_notification_without_manage_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        $this->expectException(notification_exception::class);
        $this->expectExceptionMessage(get_string('error_manage_notification', 'totara_notification'));

        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $system_built_in->get_id(),
                    'body' => 'Newly updated body',
                    'subject' => 'Newly updated subject',
                ]
            );
        } catch (\Exception $ex) {
            $this->resetDebugging();
            throw $ex;
        }
    }

    /**
     * @return void
     */
    public function test_user_can_update_notification_with_manage_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/notification:managenotifications', CAP_ALLOW, $role_id, SYSCONTEXTID, true);

        $preference = $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $system_built_in->get_id(),
                'body' => 'Newly updated body',
                'subject' => 'Newly updated subject',
            ]
        );
        $this->assertDebuggingCalledCount(2);

        self::assertInstanceOf(model::class, $preference);
        self::assertEquals($system_built_in->get_id(), $preference->get_id());
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_with_forced_delivery_channels(): void {
        global $DB;

        $this->setAdminUser();
        $generator = generator::instance();

        $custom_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            ['forced_delivery_channels' => ['email', 'popup']]
        );

        self::assertEquals(
            "[\"email\",\"popup\"]",
            $DB->get_field(
                entity::TABLE,
                'forced_delivery_channels',
                ['id' => $custom_preference->get_id()]
            )
        );

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $custom_preference->get_id(),
                'forced_delivery_channels' => ['totara_task', 'totara_alert'],
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $forced_delivery_channels_field = $DB->get_field(
            entity::TABLE,
            'forced_delivery_channels',
            ['id' => $custom_preference->get_id()]
        );

        self::assertNotEquals("[\"email\",\"popup\"]", $forced_delivery_channels_field);
        self::assertEquals("[\"totara_task\",\"totara_alert\"]", $forced_delivery_channels_field);

        $custom_preference->refresh();
        self::assertEquals(
            ['totara_task', 'totara_alert'],
            $custom_preference->get_forced_delivery_channels()
        );
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_with_invalid_forced_delivery_channels(): void {
        $this->setAdminUser();
        $generator = generator::instance();

        $custom_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system()
        );

        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $custom_preference->get_id(),
                    'forced_delivery_channels' => ['email', 'giac', 'mo', 'hom', 'qua'],
                ]
            );

            self::fail("Expect an exception to be thrown");
        } catch (Throwable $e) {
            $this->resetDebugging();
            self::assertInstanceOf(coding_exception::class, $e);
            self::assertStringContainsString(
                "The channel 'giac' is not a valid delivery channel class",
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_update_notification_preference_with_valid_forced_delivery_channels_for_built_in(): void {
        $generator = generator::instance();
        $built_in = $generator->add_mock_built_in_notification_for_component();

        $this->setAdminUser();
        self::assertEquals([], $built_in->get_forced_delivery_channels());

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $built_in->get_id(),
                'forced_delivery_channels' => ['email', 'msteams'],
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $built_in->refresh();
        self::assertEquals(['email', 'msteams'], $built_in->get_forced_delivery_channels());
    }

    /**
     * @return void
     * @throws coding_exception
     */
    public function test_update_custom_notification_with_no_forced_delivery_channels(): void {
        $this->setAdminUser();
        $generator = generator::instance();

        $patriarch_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            ['title' => 'Old title', 'forced_delivery_channels' => ['email', 'popup']]
        );

        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $patriarch_preference->get_id(),
                    'forced_delivery_channels' => null,
                ]
            );
            $this->fail('expected error when nulling forced_delivery_channels on a patriach notification_preference');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString(
                'forced_delivery_channels cannot be null if no ancestor exists',
                $ex->getMessage()
            );
        }

        // test with parents.
        $data_generator = self::getDataGenerator();
        $course = $data_generator->create_course();
        $context_course = context_course::instance($course->id);

        $child_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course),
            [
                'ancestor_id' => $patriarch_preference->get_id(),
                'forced_delivery_channels' => ['totara_alert']
            ]
        );
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $child_preference->get_id(),
                'forced_delivery_channels' => null,
            ]
        );
        $this->resetDebugging();
        $child_preference->refresh();
        $this->assertEqualsCanonicalizing(['email', 'popup'], $child_preference->get_forced_delivery_channels());
    }

    /**
     * @return void
     */
    public function test_update_built_in_should_yield_overridden_event(): void {
        $generator = generator::instance();
        $built_in = $generator->add_mock_built_in_notification_for_component();

        $this->setAdminUser();
        $event_sink = self::redirectEvents();

        self::assertEquals(0, $event_sink->count());
        self::assertEmpty($event_sink->get_events());

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $built_in->get_id(),
                'subject' => 'boom',
                'subject_format' => FORMAT_PLAIN,
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $events = $event_sink->get_events();
        self::assertCount(1, $events);

        $event = reset($events);
        self::assertInstanceOf(update_overridden_notification_preference_event::class, $event);
        self::assertArrayHasKey('overridden_fields', $event->other);
        self::assertEquals(['subject', 'subject_format'], $event->other['overridden_fields']);
    }

    /**
     * @return void
     */
    public function test_update_custom_should_yield_update_custom_event(): void {
        $generator = generator::instance();
        $custom_preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            ['title' => 'Old title']
        );

        self::assertEquals('Old title', $custom_preference->get_title());

        $this->setAdminUser();
        $event_sink = self::redirectEvents();

        self::assertEquals(0, $event_sink->count());
        self::assertEmpty($event_sink->get_events());

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $custom_preference->get_id(),
                'title' => 'New title',
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $events = $event_sink->get_events();
        self::assertNotEmpty($events);
        self::assertCount(1, $events);

        $event = reset($events);
        self::assertInstanceOf(update_custom_notification_preference_event::class, $event);

        $custom_preference->refresh();
        self::assertNotEquals('Old title', $custom_preference->get_title());
        self::assertEquals('New title', $custom_preference->get_title());
    }

    /**
     * @return void
     */
    public function test_update_overridden_custom_record_at_lower_context_should_yield_update_overridden_event(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $notification_generator = generator::instance();
        $system_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            []
        );

        $course_custom = $notification_generator->create_overridden_notification_preference(
            $system_custom,
            extended_context::make_with_context(context_course::instance($course->id)),
            [
                'subject' => 'Better subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        $event_sink = self::redirectEvents();
        self::assertEquals(0, $event_sink->count());
        self::assertEmpty($event_sink->get_events());

        $this->setAdminUser();
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $course_custom->get_id(),
                'body' => 'Boom',
                'body_format' => FORMAT_PLAIN,
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $events = $event_sink->get_events();
        self::assertCount(1, $events);
        self::assertNotEmpty($events);

        $event = reset($events);
        self::assertInstanceOf(update_overridden_notification_preference_event::class, $event);
        self::assertArrayHasKey('overridden_fields', $event->other);
        self::assertEquals(['body', 'body_format'], $event->other['overridden_fields']);
    }

    /**
     * @return void
     */
    public function test_update_custom_notification_preference_with_enabled_status(): void {
        $generator = generator::instance();
        $custom_notification = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => false
            ]
        );

        self::assertFalse($custom_notification->get_enabled());
        $this->setAdminUser();
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $custom_notification->get_id(),
                'enabled' => true
            ]
        );
        $this->assertDebuggingCalledCount(2);

        $custom_notification->refresh();
        self::assertTrue($custom_notification->get_enabled());
    }

    /**
     * @return void
     */
    public function test_update_custom_notification_preference_with_enabled_status_and_without_parent(): void {
        $generator = generator::instance();
        $custom_notification = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => false
            ]
        );

        self::assertFalse($custom_notification->get_enabled());
        $this->setAdminUser();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Cannot reset the field 'enabled' for custom notification that does not have parent(s)"
        );

        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $custom_notification->get_id(),
                    'enabled' => null
                ]
            );
        } catch (\Exception $ex) {
            $this->resetDebugging();
            if ($ex instanceof coding_exception) {
                throw $ex;
            }
        }
    }

    /**
     * @return void
     */
    public function test_update_custom_notification_preference_with_enabled_status_and_with_parent(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $notification_generator = generator::instance();
        $system_notification = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => false
            ]
        );

        $course_notification = $notification_generator->create_overridden_notification_preference(
            $system_notification,
            extended_context::make_with_context(context_course::instance($course->id)),
            ['enabled' => true]
        );

        self::assertFalse($system_notification->get_enabled());
        self::assertTrue($course_notification->get_enabled());

        $this->setAdminUser();
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $course_notification->get_id(),
                'enabled' => null
            ]
        );
        $this->assertDebuggingCalledCount(2);
        $system_notification->refresh();
        $course_notification->refresh();

        self::assertFalse($system_notification->get_enabled());
        self::assertFalse($course_notification->get_enabled());
    }

    /**
     * @return void
     */
    public function test_user_can_update_notification_with_permission_granted_by_resolver(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $ec = extended_context::make_system();

        $notification_generator = generator::instance();
        $custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            $ec,
            ['recipient' => totara_notification_mock_recipient::class]
        );

        mock_resolver::set_permissions($ec, $user_one->id, true);
        $this->setUser($user_one);

        try {
            /** @var model $updated_custom */
            $updated_custom = $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_notification_preference::class),
                [
                    'id' => $custom->get_id(),
                    'subject' => 'new subject',
                    'subject_format' => FORMAT_PLAIN
                ]
            );
            $this->assertDebuggingCalledCount(2);

            self::assertInstanceOf(model::class, $updated_custom);
            self::assertEquals($custom->get_id(), $updated_custom->get_id());
        } catch (notification_exception $e) {
            self::fail("Expecting an exception to not be thrown");
        }

        self::assertNotEquals($custom->get_subject(), $updated_custom->get_subject());
    }

    /**
     * This test ensures that anyone still calling the deprecated endpoint will also have recipients updated so
     * that removal of recipient will still have up to date recipients
     *
     * @return void
     */
    public function test_updating_recipients_are_updated_by_recipient() {
        $this->setAdminUser();
        $generator = self::getDataGenerator();
        // $user_one = $generator->create_user();

        $ec = extended_context::make_system();

        $notification_generator = generator::instance();
        $custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            $ec,
            ['recipient' => totara_notification_mock_recipient::class]
        );

        $updated_custom = $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_notification_preference::class),
            [
                'id' => $custom->get_id(),
                'subject' => 'new subject',
                'subject_format' => FORMAT_PLAIN,
                'recipient' => \totara_notification\recipient\manager::class,
            ]
        );
        $this->assertDebuggingCalledCount(2);

        self::assertInstanceOf(model::class, $updated_custom);
        self::assertEquals($custom->get_id(), $updated_custom->get_id());
        self::assertSame([\totara_notification\recipient\manager::class], $updated_custom->get_recipients());
    }
}