<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

use container_workspace\enrol\manager;
use container_workspace\member\member;
use container_workspace\testing\generator as workspace_generator;
use container_workspace\totara_notification\placeholder\enrolment as enrolment_placeholder;
use container_workspace\totara_notification\placeholder\workspace as workspace_placeholder;
use container_workspace\totara_notification\recipient\workspace_owner;
use container_workspace\totara_notification\resolver\user_added;
use container_workspace\totara_notification\workspace_muter;
use core\entity\user_enrolment;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_totara_notification_user_added_resolver_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $workspace;
    private $workspace_owner;
    private $preference;

    public function tearDown(): void {
        workspace_muter::reset();

        $this->workspace = null;
        $this->workspace_owner = null;
        $this->preference = null;

        parent::tearDown();
    }

    /**
     * @return array
     */
    public function test_resolver_data_provider(): array {
        return [
            [0],
            [1],
            [2],
        ];
    }

    /**
     * @dataProvider test_resolver_data_provider
     * @param int $audience_count
     */
    public function test_resolver_user_added(int $audience_count): void {
        global $DB;

        self::setAdminUser();

        // Ensure all are empty
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $generator = self::getDataGenerator();
        $member = $generator->create_user(['firstname' => 'Workspace member firstname', 'lastname' => 'Workspace member lastname']);

        if ($audience_count > 0) {
            $generator = self::getDataGenerator();
            $member_manager = manager::from_workspace($this->workspace);

            for ($i = 0; $i <= $audience_count; $i++) {
                $audience = $generator->create_cohort();
                cohort_add_member($audience->id, $member->id);
                $member_manager->enrol_audiences([$audience->id]);
            }

            enrol_cohort_sync(new null_progress_trace(), $this->workspace->get_id());
        } else {
            member::added_to_workspace($this->workspace, $member->id);
        }

        // For testing purposes, we're going to set a static enrolled time
        $DB->set_field(user_enrolment::TABLE, 'timestart', 1620143913, ['userid' => $member->id]);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => user_added::class]));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        // Only one notification was processed, because the other built-in notifications were disabled.
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test workspace user added notification subject', $message->subject);
        self::assertStringContainsString('User added to workspace test', $message->fullmessage);
        self::assertStringContainsString('Workspace member lastname', $message->fullmessage); // Subject
        self::assertStringContainsString('Workspace owner lastname', $message->fullmessage); // Recipient
        self::assertStringContainsString('Test Workspace', $message->fullmessage); // Workspace
        self::assertStringContainsString('Tuesday, 4 May 2021, 11:58 PM', $message->fullmessage); // Enrolment
        self::assertEquals($this->workspace_owner->id, $message->userto->id);

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => user_added::class,
                'context_id' => $this->workspace->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_user_added', 'container_workspace', [
                    'resolver_title' => user_added::get_notification_title(),
                    'user' => 'Workspace member firstname Workspace member lastname',
                    'workspace' => 'Test Workspace',
                ])
            ],
        ]);
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        workspace_muter::reset();

        // Delete built-in notifications.
        builder::table('notification_preference')->delete();

        $workspace_generator = workspace_generator::instance();
        $generator = self::getDataGenerator();

        // Create the workspace
        $this->workspace_owner = $generator->create_user(['lastname' => 'Workspace owner lastname']);
        $this->setUser($this->workspace_owner);
        $this->workspace = $workspace_generator->create_workspace(
            'Test Workspace',
            null,
            null,
            $this->workspace_owner->id
        );

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $this->preference = $notification_generator->create_notification_preference(
            user_added::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => workspace_owner::class,
                'recipients' => [workspace_owner::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('User added to workspace test'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('subject:last_name', 'Subject last name'),
                            placeholder::create_node_from_key_and_label('workspace:full_name', 'Workspace full name'),
                            placeholder::create_node_from_key_and_label('enrolment:join_date', 'Workspace join date'),
                        ]),
                    ])
                ),
                'subject' => 'Test workspace user added notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );
    }
}
