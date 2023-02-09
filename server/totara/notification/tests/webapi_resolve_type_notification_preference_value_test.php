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

use core\format;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use totara_notification\entity\notification_preference;
use totara_notification\model\notification_preference_value as model;
use totara_notification\testing\generator;
use totara_notification\webapi\resolver\type\notification_preference_value;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_core\extended_context;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_phpunit\testcase;

class totara_notification_webapi_resolve_type_notification_preference_value_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var model|null
     */
    private $preference_value;

    /**
     * @return void
     */
    protected function setUp(): void {
        /** @var generator $notification_generator */
        $notification_generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $notification_generator->include_mock_notifiable_event_resolver();
        $notification_generator->include_mock_recipient();

        $custom_notification = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'title' => 'This is custom title',
                'body' => 'This is custom body',
                'body_format' => FORMAT_MOODLE,
                'subject' => 'This is custom subject',
                'subject_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        $this->preference_value = model::from_parent_notification_preference($custom_notification);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->preference_value = null;
    }

    /**
     * @return void
     */
    public function test_resolve_field_title(): void {
        self::assertEquals(
            'This is custom title',
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference_value::class),
                'title',
                $this->preference_value
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_subject(): void {
        self::assertEquals(
            'This is custom subject',
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference_value::class),
                'subject',
                $this->preference_value
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_body(): void {
        self::assertEquals(
            'This is custom body',
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference_value::class),
                'body',
                $this->preference_value
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_body_format(): void {
        self::assertEquals(
            FORMAT_MOODLE,
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference_value::class),
                'body_format',
                $this->preference_value
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_subject_format(): void {
        self::assertEquals(
            FORMAT_MOODLE,
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference_value::class),
                'subject_format',
                $this->preference_value
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_subject_with_format_json_editor_as_content_format_and_raw_as_format(): void {
        global $DB;
        $generator = generator::instance();

        // First we will create a preference record that has subject_format as FORMAT_PLAIN so that it will not get
        // to convert the subject into a json document content.
        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'subject_format' => FORMAT_PLAIN,
                'subject' => 'This is subject',
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        // Then we will update the field subject_format to JSON_EDITOR hence we can
        // check if the content get formatted by the output or not.
        $record = new stdClass();
        $record->id = $preference->get_id();
        $record->subject_format = FORMAT_JSON_EDITOR;
        $DB->update_record(notification_preference::TABLE, $record);

        // Refresh our model with newly updated fields.
        $preference->refresh();
        $preference_value = model::from_parent_notification_preference($preference);

        self::assertEquals(
            json_encode([
                'type' => document_helper::DOC_TYPE_NAME,
                'content' => [
                    paragraph::create_json_node_from_text('This is subject'),
                ],
            ]),
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference_value::class),
                'subject',
                $preference_value,
                ['format' => format::FORMAT_RAW]
            )
        );
    }


    /**
     * @return void
     */
    public function test_resolve_field_body_with_format_json_editor_as_content_format_and_raw_as_format(): void {
        global $DB;
        $generator = generator::instance();

        // First we will create a preference record that has body_format as FORMAT_PLAIN so that it will not get
        // to convert the body into a json document content.
        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'body_format' => FORMAT_PLAIN,
                'body' => 'This is body',
                'recipient' => totara_notification_mock_recipient::class,
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        // Then we will update the field body_format to JSON_EDITOR hence we can
        // check if the content get formatted by the output or not.
        $record = new stdClass();
        $record->id = $preference->get_id();
        $record->body_format = FORMAT_JSON_EDITOR;
        $DB->update_record(notification_preference::TABLE, $record);

        // Refresh our model with newly updated fields.
        $preference->refresh();
        $preference_value = model::from_parent_notification_preference($preference);

        self::assertEquals(
            json_encode([
                'type' => document_helper::DOC_TYPE_NAME,
                'content' => [
                    paragraph::create_json_node_from_text('This is body'),
                ],
            ]),
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference_value::class),
                'body',
                $preference_value,
                ['format' => format::FORMAT_RAW]
            )
        );
    }
}