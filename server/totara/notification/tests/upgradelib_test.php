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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference;
use totara_notification\entity\notifiable_event_preference as notification_event_preference_entity;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\model\notifiable_event_preference as notification_event_preference_model;
use totara_notification\model\notifiable_event_user_preference as notifiable_event_user_preference_model;
use totara_notification\model\notification_preference as notification_preference_model;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_notification_mock_scheduled_aware_event_resolver as scheduled_resolver;

class totara_notification_upgradelib_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();
    }

    /**
     * @return notification_preference_model
     */
    private function create_new_notification_preference(): notification_preference_model {
        $generator = generator::instance();

        return $generator->create_notification_preference(mock_resolver::class);
    }

    /**
     * @param string $provider_name
     * @param string $provider_component
     * @param bool   $enabled
     *
     * @return void
     */
    private function set_legacy_preference_status(string $provider_name, string $provider_component, bool $enabled): void {
        $name = $provider_component . '_' . $provider_name . '_disabled';
        set_config($name, (int)(!$enabled), 'message');
    }

    /**
     * @param string $provider_name
     * @param string $provider_component
     * @param string $output
     * @param string $permitted
     *
     * @return void
     */
    private function set_legacy_preference_permissions(
        string $provider_name,
        string $provider_component,
        string $output,
        string $permitted // 'disallowed', 'permitted' or 'forced'
    ): void {
        $name = $output . '_provider_' . $provider_component . '_' . $provider_name . '_permitted';
        set_config($name, $permitted, 'message');
    }

    private function set_legacy_preference_default_outputs(
        string $provider_name,
        string $provider_component,
        string $outputs_enabled_loggedin,
        string $outputs_enabled_loggedoff
    ): void {
        $name = 'message_provider_' . $provider_component . '_' . $provider_name;
        set_config($name . '_loggedin', $outputs_enabled_loggedin, 'message');
        set_config($name . '_loggedoff', $outputs_enabled_loggedoff, 'message');
    }

    /**
     * Test that the correct outputs are enabled and disabled in the new notifiable event.
     * @return void
     */
    public function test_totara_notification_migrate_notifiable_event_prefs_with_existing_record(): void {
        $control_notifiable_event_entity = new notification_event_preference_entity();
        $control_notifiable_event_entity->resolver_class_name = mock_resolver::class;
        $control_notifiable_event_entity->context_id = context_system::instance()->id;
        $control_notifiable_event_entity->save();
        $control_notifiable_event = notification_event_preference_model::from_entity($control_notifiable_event_entity);
        $control_enabled_delivery_channels = $control_notifiable_event->get_default_delivery_channels();

        $target_notifiable_event_entity = new notification_event_preference_entity();
        $target_notifiable_event_entity->resolver_class_name = scheduled_resolver::class;
        $target_notifiable_event_entity->context_id = context_system::instance()->id;
        $target_notifiable_event_entity->save();
        $target_notifiable_event = notification_event_preference_model::from_entity($target_notifiable_event_entity);

        $this->set_legacy_preference_default_outputs(
            'alert',
            'totara_message',
            'totara_alert,msteams',
            'totara_alert,email'
        );

        totara_notification_migrate_notifiable_event_prefs(
            $target_notifiable_event->resolver_class_name,
            'alert',
            'totara_message'
        );
        $target_notifiable_event->refresh();
        $target_delivery_channels_enabled = $target_notifiable_event->get_default_delivery_channels();

        // Check that the control is unaffected.
        $control_notifiable_event->refresh();
        self::assertEquals($control_enabled_delivery_channels, $control_notifiable_event->get_default_delivery_channels());

        // Case where both loggedin and loggedoff are off.
        self::assertFalse($target_delivery_channels_enabled['popup']->is_enabled);

        // Case where both loggedin and loggedoff are on.
        self::assertTrue($target_delivery_channels_enabled['totara_alert']->is_enabled);

        // Case where loggedin is on and loggedoff is off.
        self::assertTrue($target_delivery_channels_enabled['msteams']->is_enabled);

        // Case where loggedin is off and loggedoff is on.
        self::assertTrue($target_delivery_channels_enabled['email']->is_enabled);
    }

    /**
     * @return void
     */
    public function test_totara_notification_migrate_notifiable_event_prefs_with_no_record(): void {
        $extended_context = extended_context::make_with_context(context_system::instance());

        $this->set_legacy_preference_default_outputs(
            'alert',
            'totara_message',
            'totara_alert,msteams',
            'totara_alert,email'
        );

        totara_notification_migrate_notifiable_event_prefs(
            mock_resolver::class,
            'alert',
            'totara_message'
        );
        $target_notifiable_event_entity = notification_event_preference_entity::repository()
            ->for_context(mock_resolver::class, $extended_context);
        $target_notifiable_event = notification_event_preference_model::from_entity($target_notifiable_event_entity);
        $target_delivery_channels_enabled = $target_notifiable_event->get_default_delivery_channels();

        // Check that the control is unaffected.
        $control_notifiable_event_entity = notification_event_preference_entity::repository()
            ->for_context(scheduled_resolver::class, $extended_context);
        self::assertEmpty($control_notifiable_event_entity);

        // Case where both loggedin and loggedoff are off.
        self::assertFalse($target_delivery_channels_enabled['popup']->is_enabled);

        // Case where both loggedin and loggedoff are on.
        self::assertTrue($target_delivery_channels_enabled['totara_alert']->is_enabled);

        // Case where loggedin is on and loggedoff is off.
        self::assertTrue($target_delivery_channels_enabled['msteams']->is_enabled);

        // Case where loggedin is off and loggedoff is on.
        self::assertTrue($target_delivery_channels_enabled['email']->is_enabled);
    }

    /**
     * Tests that legacy message preferences are correctly copied over to notifiable event user preferences.
     */
    public function test_totara_notification_migrate_notifiable_event_prefs_user_prefs(): void {
        // Set up.
        $user0 = self::getDataGenerator()->create_user();
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();
        $user5 = self::getDataGenerator()->create_user();
        $user6 = self::getDataGenerator()->create_user();

        set_user_preferences([
            'message_provider_resolver_mock_loggedin' => '',
        ], $user0->id);

        set_user_preferences([
            'message_provider_resolver_mock_loggedin' => 'one,two',
            'message_provider_resolver_mock_loggedoff' => 'two,three',
        ], $user1->id);

        set_user_preferences([
            'message_provider_resolver_mock_loggedin' => 'one',
        ], $user2->id);

        set_user_preferences([
            'message_provider_resolver_mock_loggedoff' => 'two',
        ], $user3->id);

        set_user_preferences([
            'message_provider_resolver_mock_loggedin' => 'one',
            'message_provider_resolver_mock_loggedoff' => 'two',
        ], $user4->id);

        set_user_preferences([
            'message_provider_resolver_mock_loggedoff' => 'five',
        ], $user5->id);

        // Control records - not migrated.
        set_user_preferences([
            'message_provider_resolver_control_loggedin' => 'one,two',
            'message_provider_resolver_control_loggedoff' => 'two,three',
        ], $user3->id);

        set_user_preferences([
            'message_provider_resolver_control_loggedin' => 'six',
        ], $user6->id);

        // Do the migration (tests 2nd to 5th code paths).
        totara_notification_migrate_notifiable_event_prefs(mock_resolver::class, 'mock', 'resolver');

        // Check results.
        $preference_entities = notifiable_event_user_preference_entity::repository()->get();
        self::assertCount(6, $preference_entities);

        /** @var notifiable_event_user_preference_entity $preference_entity */
        foreach ($preference_entities as $preference_entity) {
            self::assertEquals(mock_resolver::class, $preference_entity->resolver_class_name);
            self::assertEquals(extended_context::make_system()->get_context_id(), $preference_entity->context_id);
            self::assertEquals(extended_context::NATURAL_CONTEXT_COMPONENT, $preference_entity->component);
            self::assertEquals(extended_context::NATURAL_CONTEXT_AREA, $preference_entity->area);
            self::assertEquals(extended_context::NATURAL_CONTEXT_ITEM_ID, $preference_entity->item_id);
            self::assertEquals(1, $preference_entity->enabled);
            switch ($preference_entity->user_id) {
                case $user0->id:
                    self::assertEqualsCanonicalizing([], $preference_entity->delivery_channels);
                    break;
                case $user1->id:
                    self::assertEqualsCanonicalizing(['one', 'two', 'three'], $preference_entity->delivery_channels);
                    break;
                case $user2->id:
                    self::assertEqualsCanonicalizing(['one'], $preference_entity->delivery_channels);
                    break;
                case $user3->id:
                    self::assertEqualsCanonicalizing(['two'], $preference_entity->delivery_channels);
                    break;
                case $user4->id:
                    self::assertEqualsCanonicalizing(['one', 'two'], $preference_entity->delivery_channels);
                    break;
                case $user5->id:
                    self::assertEqualsCanonicalizing(['five'], $preference_entity->delivery_channels);
                    break;
                default:
                    self::fail('Unexpected user id found');
            }
        }

        // Do the migration (tests first code path).
        totara_notification_migrate_notifiable_event_prefs(mock_resolver::class, 'control', 'resolver');

        // Check results.
        $preference_entities = notifiable_event_user_preference_entity::repository()
            ->where('user_id', $user6->id)
            ->get();
        self::assertCount(1, $preference_entities);

        /** @var notifiable_event_user_preference_entity $preference_entity */
        $preference_entity = $preference_entities->first();
        self::assertEqualsCanonicalizing(['six'], $preference_entity->delivery_channels);
    }

    /**
     * Tests that one legacy user preference is correctly migrated with the correct values.
     */
    public function test_totara_notification_migrate_notification_user_pref_with_one_legacy_preference(): void {
        // Set up.
        $user = self::getDataGenerator()->create_user();

        $legacy_preference = new stdClass();
        $legacy_preference->userid = $user->id;
        $legacy_preference->value = 'one,two';

        // Do the migration.
        totara_notification_migrate_notification_user_pref(
            mock_resolver::class,
            $legacy_preference
        );

        // Check results.
        $preference_entities = notifiable_event_user_preference_entity::repository()->get();
        self::assertCount(1, $preference_entities);
        /** @var notifiable_event_user_preference_entity $preference_entity */
        $preference_entity = $preference_entities->first();
        self::assertEquals(mock_resolver::class, $preference_entity->resolver_class_name);
        self::assertEquals($user->id, $preference_entity->user_id);
        self::assertEquals(extended_context::make_system()->get_context_id(), $preference_entity->context_id);
        self::assertEquals(extended_context::NATURAL_CONTEXT_COMPONENT, $preference_entity->component);
        self::assertEquals(extended_context::NATURAL_CONTEXT_AREA, $preference_entity->area);
        self::assertEquals(extended_context::NATURAL_CONTEXT_ITEM_ID, $preference_entity->item_id);
        self::assertEquals(1, $preference_entity->enabled);
        self::assertEqualsCanonicalizing(['one', 'two'], $preference_entity->delivery_channels);
    }

    /**
     * Tests that two legacy user preferences (loggedin and loggedoff) are correctly combined into one notification preference.
     */
    public function test_totara_notification_migrate_notification_user_pref_with_two_legacy_preferences(): void {
        // Set up.
        $user = self::getDataGenerator()->create_user();

        $legacy_preference1 = new stdClass();
        $legacy_preference1->userid = $user->id;
        $legacy_preference1->name = 'pref_loggedin';
        $legacy_preference1->value = 'one,two';

        $legacy_preference2 = new stdClass();
        $legacy_preference2->userid = $user->id;
        $legacy_preference2->name = 'pref_loggedoff';
        $legacy_preference2->value = 'two,three';

        // Do the migration.
        totara_notification_migrate_notification_user_pref(
            mock_resolver::class,
            $legacy_preference1,
            $legacy_preference2
        );

        // Check results.
        $preference_entities = notifiable_event_user_preference_entity::repository()->get();
        self::assertCount(1, $preference_entities);
        /** @var notifiable_event_user_preference_entity $preference_entity */
        $preference_entity = $preference_entities->first();
        self::assertEquals(mock_resolver::class, $preference_entity->resolver_class_name);
        self::assertEquals($user->id, $preference_entity->user_id);
        self::assertEquals(extended_context::make_system()->get_context_id(), $preference_entity->context_id);
        self::assertEquals(extended_context::NATURAL_CONTEXT_COMPONENT, $preference_entity->component);
        self::assertEquals(extended_context::NATURAL_CONTEXT_AREA, $preference_entity->area);
        self::assertEquals(extended_context::NATURAL_CONTEXT_ITEM_ID, $preference_entity->item_id);
        self::assertEquals(1, $preference_entity->enabled);
        self::assertEqualsCanonicalizing(['one', 'two', 'three'], $preference_entity->delivery_channels);
    }

    /**
     * Tests that one legacy user preference is correctly migrated with the correct values.
     */
    public function test_totara_notification_migrate_notification_user_pref_with_existing_new_preference(): void {
        // Set up.
        $user = self::getDataGenerator()->create_user();

        $legacy_preference = new stdClass();
        $legacy_preference->userid = $user->id;
        $legacy_preference->value = 'one,two';

        notifiable_event_user_preference_model::create(
            $user->id,
            mock_resolver::class,
            extended_context::make_system(),
            true,
            ['two', 'three']
        );

        // Do the migration.
        totara_notification_migrate_notification_user_pref(
            mock_resolver::class,
            $legacy_preference
        );

        // Check results.
        $preference_entities = notifiable_event_user_preference_entity::repository()->get();
        self::assertCount(1, $preference_entities);
        /** @var notifiable_event_user_preference_entity $preference_entity */
        $preference_entity = $preference_entities->first();
        self::assertEquals(mock_resolver::class, $preference_entity->resolver_class_name);
        self::assertEquals($user->id, $preference_entity->user_id);
        self::assertEquals(extended_context::make_system()->get_context_id(), $preference_entity->context_id);
        self::assertEquals(extended_context::NATURAL_CONTEXT_COMPONENT, $preference_entity->component);
        self::assertEquals(extended_context::NATURAL_CONTEXT_AREA, $preference_entity->area);
        self::assertEquals(extended_context::NATURAL_CONTEXT_ITEM_ID, $preference_entity->item_id);
        self::assertEquals(1, $preference_entity->enabled);
        self::assertEqualsCanonicalizing(['one', 'two', 'three'], $preference_entity->delivery_channels);
    }

    /**
     * Test that the enabled/disabled legacy notification preference results in a new notification that is enabled or disabled.
     */
    public function test_totara_notification_migrate_notification_prefs_status(): void {
        $control_notif_preference = $this->create_new_notification_preference();
        $control_enabled = $control_notif_preference->get_enabled();

        $new_notif_preference = $this->create_new_notification_preference();

        // Case where legacy notification is enabled.
        $this->set_legacy_preference_status('alert', 'totara_message', true);
        totara_notification_migrate_notification_prefs(
            $new_notif_preference->get_id(),
            'alert',
            'totara_message'
        );
        $new_notif_preference->refresh();
        self::assertTrue($new_notif_preference->get_enabled());

        // The control is unaffected.
        $control_notif_preference->refresh();
        self::assertEquals($control_enabled, $control_notif_preference->get_enabled());

        // Case where legacy notification is disabled.
        $this->set_legacy_preference_status('alert', 'totara_message', false);
        totara_notification_migrate_notification_prefs(
            $new_notif_preference->get_id(),
            'alert',
            'totara_message'
        );
        $new_notif_preference->refresh();
        self::assertFalse($new_notif_preference->get_enabled());

        // The control is unaffected.
        $control_notif_preference->refresh();
        self::assertEquals($control_enabled, $control_notif_preference->get_enabled());
    }

    /**
     * Test that a legacy notification that is 'locked' results in forced delivery in the new notification.
     * Also tests that 'disallowed' and 'permitted' have no effect.
     */
    public function test_totara_notification_migrate_notification_prefs_permissions(): void {
        $control_notif_preference = $this->create_new_notification_preference();
        $control_forced_delivery_channels = $control_notif_preference->get_forced_delivery_channels();
        $new_notif_preference = $this->create_new_notification_preference();

        $this->set_legacy_preference_permissions(
            'alert',
            'totara_message',
            'totara_alert',
            'forced'
        );

        $this->set_legacy_preference_permissions(
            'alert',
            'totara_message',
            'email',
            'disallowed'
        );

        $this->set_legacy_preference_permissions(
            'alert',
            'totara_message',
            'popup',
            'permitted'
        );

        totara_notification_migrate_notification_prefs(
            $new_notif_preference->get_id(),
            'alert',
            'totara_message'
        );
        $new_notif_preference->refresh();
        $forcer_delivery_channels = $new_notif_preference->get_forced_delivery_channels();

        // The control is unaffected.
        $control_notif_preference->refresh();
        self::assertEquals($control_forced_delivery_channels, $control_notif_preference->get_forced_delivery_channels());

        // Check that a forced legacy output results in forced delivery in the new notification.
        self::assertContains('totara_alert', $forcer_delivery_channels);

        // Check that a permitted legacy output results in no forced delivery in the new notification.
        self::assertNotContains('email', $forcer_delivery_channels);

        // Check that a disabled legacy output results in no forced delivery in the new notification.
        self::assertNotContains('popup', $forcer_delivery_channels);
    }

    /**
     * Just to be safe to check upgrade_notification_event_log field type change
     *
     * @return void
     */
    public function test_upgrade_notification_event_log(): void {
        global $DB;

        // Mock it to change it to 100 length
        $dbman = $DB->get_manager();
        $table = new xmldb_table('notification_event_log');
        $field = new xmldb_field('display_string_params', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'display_string_key');
        $dbman->change_field_type($table, $field);

        $generator = self::getDataGenerator();

        $user = $generator->create_user();

        try {
            $notification_event_log = \totara_notification\model\notification_event_log::create(
                'some_resolver_class',
                extended_context::make_system(),
                $user->id,
                ['fld1' => 'a', 'fld2' => 'b'],
                'some_schedule_class',
                -123,
                'string_key',
                [
                    'component' => "totara_mock",
                    "params" => [
                        'resolver_title' => 'resolver_title',
                        'user' => 'Tom Jackson',
                        'course' => 'course name is very very very very very very very very very very long.',
                        'activity' => 'Activity name is very very very very very very very very very long.'
                    ]
                ],
                time(),
            );
            $this->fail('Exception expected!');
        } catch (dml_write_exception $e) {
            $this->assertEquals('dmlwriteexception', $e->errorcode);
        }

        // Update field type
        $dbman = $DB->get_manager();
        $table = new xmldb_table('notification_event_log');
        $field = new xmldb_field('display_string_params', XMLDB_TYPE_TEXT, null, null, null, null, null, 'display_string_key');
        $dbman->change_field_type($table, $field);

        $notification_event_log = \totara_notification\model\notification_event_log::create(
            'some_resolver_class',
            extended_context::make_system(),
            $user->id,
            ['fld1' => 'a', 'fld2' => 'b'],
            'some_schedule_class',
            -123,
            'string_key',
            [
                'component' => "totara_mock",
                "params" => [
                    'resolver_title' => 'resolver_title',
                    'user' => 'Tom Jackson',
                    'course' => 'course name is very very very very very very very very very very long.',
                    'activity' => 'Activity name is very very very very very very very very very long.'
                ]
            ],
            time(),
        );

        // Check again
        $display_string_params = $DB->get_field('notification_event_log', 'display_string_params', ['id' => $notification_event_log->get_id()]);
        self::assertStringContainsString(
            '{"component":"totara_mock","params":{"resolver_title":"resolver_title","user":"Tom Jackson","course":"course name is very very very very very very very very very very long.","activity":"Activity name is very very very very very very very very very long."}}',
            $display_string_params
        );

        self::assertEquals(
            strlen('{"component":"totara_mock","params":{"resolver_title":"resolver_title","user":"Tom Jackson","course":"course name is very very very very very very very very very very long.","activity":"Activity name is very very very very very very very very very long."}}'),
            strlen($display_string_params)
        );

        self::assertEquals(256, strlen($display_string_params));
    }

    /**
     * Suppose plan text exists with FORMAT_JSON_EDITOR. This should not have existed.
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public function totara_notification_upgrade_convert_invalid_line_break_with_text(): void {
        global $DB;
        $text_subject = 'subject';
        $test_body = 'body';

        // Create customer notification
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body($text_subject);
        $builder->set_subject($test_body);
        $builder->set_body_format(FORMAT_JSON_EDITOR);
        $builder->set_subject_format(FORMAT_JSON_EDITOR);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);
        $builder->set_recipient(totara_notification_mock_recipient::class);
        $preference = $builder->save();

        $decode = json_decode($test_body, true);
        self::assertTrue($DB->record_exists(notification_preference::TABLE, ['id' => $preference->get_id()]));
        $this->expectException(TypeError::class);
        $this->expectErrorMessage('totara_notification_is_new_line_affected(): Argument #1 ($decode) must be of');
        totara_notification_is_contain_invalid_line_break($decode);
    }

    /**
     * Suppose body and subject contain valid JSON and no invalid line break
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public function totara_notification_upgrade_convert_invalid_line_break_with_valid_json(): void {
        global $DB;

        $valid_json_subject = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Subject"
                }
            ]
        }
    ]
}
EOT;

        $valid_json_body = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "You are now enrolled on program [program:full_name]."
                }
            ]
        },
        {
            "type": "paragraph",
            "content": []
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "new e line "
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "newe paragraphe"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": []
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Last paragraph"
                }
            ]
        }
    ]
}
EOT;

        // Create customer notification
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body($valid_json_body);
        $builder->set_subject($valid_json_subject);
        $builder->set_body_format(FORMAT_JSON_EDITOR);
        $builder->set_subject_format(FORMAT_JSON_EDITOR);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);
        $builder->set_recipient(totara_notification_mock_recipient::class);
        $preference = $builder->save();

        $decode = json_decode($valid_json_body, true);
        self::assertTrue($DB->record_exists(notification_preference::TABLE, ['id' => $preference->get_id()]));
        // Valid json body and subject. No line affected.
        self::assertFalse(totara_notification_is_contain_invalid_line_break($decode));
        // Running "totara_notification_upgrade_new_line_to_json" should not change any content of the body.
        totara_notification_upgrade_convert_invalid_line_break();
        $json_body = $DB->get_record(notification_preference::TABLE, ['id' => $preference->get_id()], 'body', MUST_EXIST);
        self::assertEquals(
            json_encode(json_decode($valid_json_body), JSON_UNESCAPED_SLASHES),
            json_encode(json_decode($json_body->body), JSON_UNESCAPED_SLASHES)
        );
    }


    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public function totara_notification_upgrade_convert_invalid_line_break_with_line_break(): void {
        global $DB;
        $valid_json_subject = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Subject"
                }
            ]
        }
    ]
}
EOT;
        // Invalid json body
        $invalid_json_body1 = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "You are now enrolled on program [program:full_name].\r\n\r\nAssignment 1\r\nAssignment 2\r\nAssignment 3\r\nAssignment 4\r\n\r\nAssignment 5"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": []
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "new e line "
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "new paragraph"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": []
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "text"
                }
            ]
        }
    ]
}
EOT;

        $expected_json = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "You are now enrolled on program [program:full_name].",
                    "marks": []
                },
                {
                    "type": "hard_break"
                },
                {
                    "type": "hard_break"
                },
                {
                    "type": "text",
                    "text": "Assignment 1",
                    "marks": []
                },
                {
                    "type": "hard_break"
                },
                {
                    "type": "text",
                    "text": "Assignment 2",
                    "marks": []
                },
                {
                    "type": "hard_break"
                },
                {
                    "type": "text",
                    "text": "Assignment 3",
                    "marks": []
                },
                {
                    "type": "hard_break"
                },
                {
                    "type": "text",
                    "text": "Assignment 4",
                    "marks": []
                },
                {
                    "type": "hard_break"
                },
                {
                    "type": "hard_break"
                },
                {
                    "type": "text",
                    "text": "Assignment 5",
                    "marks": []
                }
            ]
        },
        {
            "type": "paragraph",
            "content": []
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "new e line "
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "new paragraph"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": []
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "text"
                }
            ]
        }
    ]
}
EOT;


        // Create customer notification
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body($invalid_json_body1);
        $builder->set_subject($valid_json_subject);
        $builder->set_body_format(FORMAT_JSON_EDITOR);
        $builder->set_subject_format(FORMAT_JSON_EDITOR);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);
        $builder->set_recipient(totara_notification_mock_recipient::class);
        $preference = $builder->save();

        $decode = json_decode($invalid_json_body1, true);
        self::assertTrue($DB->record_exists(notification_preference::TABLE, ['id' => $preference->get_id()]));
        // invalid json body.
        self::assertTrue(totara_notification_is_contain_invalid_line_break($decode));
        // Running "totara_notification_upgrade_new_line_to_json" should fix the content.
        totara_notification_upgrade_convert_invalid_line_break();
        $json_body = $DB->get_record(notification_preference::TABLE, ['id' => $preference->get_id()], 'body', MUST_EXIST);
        self::assertEquals(
            json_encode(json_decode($expected_json), JSON_UNESCAPED_SLASHES),
            json_encode(json_decode($json_body->body), JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Test with line break and Link, Rule, Emoji and Lists
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public function totara_notification_upgrade_convert_invalid_line_break_with_line_break_and_other(): void {
        global $DB;
        $valid_json_subject = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Subject"
                }
            ]
        }
    ]
}
EOT;
        // Invalid json body
        $invalid_json_body1 = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "This is an example with Rule, Emoji, Link, Number List, Bullet List. \r\n\r\n and more."
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Emoji "
                },
                {
                    "type": "emoji",
                    "attrs": {
                        "shortcode": "1F601"
                    }
                },
                {
                    "type": "emoji",
                    "attrs": {
                        "shortcode": "1F601"
                    }
                },
                {
                    "type": "emoji",
                    "attrs": {
                        "shortcode": "1F60A"
                    }
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Rule"
                }
            ]
        },
        {
            "type": "ruler"
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Link"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "marks": [
                        {
                            "type": "link",
                            "attrs": {
                                "href": "https://www.totara.com/",
                                "open_in_new_window": true
                            }
                        }
                    ],
                    "text": "Totata (opens in new window)"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Bullet list \r\n and one like break "
                }
            ]
        },
        {
            "type": "bullet_list",
            "content": [
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "One "
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "Two "
                                }
                            ]
                        }
                    ]
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Number list"
                }
            ]
        },
        {
            "type": "ordered_list",
            "attrs": {
                "order": "1"
            },
            "content": [
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "One "
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "Two"
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    ]
}
EOT;

        $expected_json = <<<EOT
{
    "type": "doc",
    "content": [
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "This is an example with Rule, Emoji, Link, Number List, Bullet List.\r\n\r\n and more."
                }
                {
                    "type": "hard_break"
                }
                {
                    "type": "text",
                    "text": " and more."
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Emoji "
                },
                {
                    "type": "emoji",
                    "attrs": {
                        "shortcode": "1F601"
                    }
                },
                {
                    "type": "emoji",
                    "attrs": {
                        "shortcode": "1F601"
                    }
                },
                {
                    "type": "emoji",
                    "attrs": {
                        "shortcode": "1F60A"
                    }
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Rule"
                }
            ]
        },
        {
            "type": "ruler"
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Link"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "marks": [
                        {
                            "type": "link",
                            "attrs": {
                                "href": "https://www.totara.com/",
                                "open_in_new_window": true
                            }
                        }
                    ],
                    "text": "Totata (opens in new window)"
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Bullet list "
                }
                {
                    "type": "hard_break"
                }
                {
                    "type": "text",
                    "text": " and one like break "
                }
            ]
        },
        {
            "type": "bullet_list",
            "content": [
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "One "
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "Two "
                                }
                            ]
                        }
                    ]
                }
            ]
        },
        {
            "type": "paragraph",
            "content": [
                {
                    "type": "text",
                    "text": "Number list"
                }
            ]
        },
        {
            "type": "ordered_list",
            "attrs": {
                "order": "1"
            },
            "content": [
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "One "
                                }
                            ]
                        }
                    ]
                },
                {
                    "type": "list_item",
                    "content": [
                        {
                            "type": "paragraph",
                            "content": [
                                {
                                    "type": "text",
                                    "text": "Two"
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    ]
}
EOT;


        // Create customer notification
        $builder = new notification_preference_builder(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance())
        );

        $builder->set_title('title');
        $builder->set_body($invalid_json_body1);
        $builder->set_subject($valid_json_subject);
        $builder->set_body_format(FORMAT_JSON_EDITOR);
        $builder->set_subject_format(FORMAT_JSON_EDITOR);
        $builder->set_schedule_offset(0);
        $builder->set_enabled(true);
        $builder->set_recipient(totara_notification_mock_recipient::class);
        $preference = $builder->save();

        $decode = json_decode($invalid_json_body1, true);
        self::assertTrue($DB->record_exists(notification_preference::TABLE, ['id' => $preference->get_id()]));
        // invalid json body.
        self::assertTrue(totara_notification_is_contain_invalid_line_break($decode));
        // Running "totara_notification_upgrade_new_line_to_json" should fix the content.
        totara_notification_upgrade_convert_invalid_line_break();
        $json_body = $DB->get_record(notification_preference::TABLE, ['id' => $preference->get_id()], 'body', MUST_EXIST);
        self::assertEquals(
            json_encode(json_decode($expected_json), JSON_UNESCAPED_SLASHES),
            json_encode(json_decode($json_body->body), JSON_UNESCAPED_SLASHES)
        );

        // Run multiple times
        totara_notification_upgrade_convert_invalid_line_break();
        $json_body1 = $DB->get_record(notification_preference::TABLE, ['id' => $preference->get_id()], 'body', MUST_EXIST);
        self::assertEquals(
            json_encode(json_decode($expected_json), JSON_UNESCAPED_SLASHES),
            json_encode(json_decode($json_body1->body), JSON_UNESCAPED_SLASHES)
        );

        // Running this again should not change anything
        totara_notification_upgrade_convert_invalid_line_break();
        $json_body2 = $DB->get_record(notification_preference::TABLE, ['id' => $preference->get_id()], 'body', MUST_EXIST);
        self::assertEquals($json_body2, $json_body1->body);
    }
}