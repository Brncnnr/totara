<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_popup
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use message_popup\webapi\resolver\mutation\mark_messages_read;
use message_popup\api as message_api;

global $CFG;

require_once($CFG->dirroot . '/message/tests/messagelib_test.php');
require_once($CFG->dirroot . '/message/output/popup/tests/base.php');

/**
 * Tests the message_popup messages query resolver
 */
class message_popup_webapi_resolver_mutation_mark_messages_read_testcase extends advanced_testcase {

    use webapi_phpunit_helper;
    use message_popup_test_helper;

    /**
     * @var \core_phpunit\message_sink|null
     */
    private $messagesink;

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->messagesink = $this->redirectMessages();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->messagesink = null;
    }

    /**
     * Create some messages for testing.
     *
     * @return array
     */
    private function create_messages(): array {
        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        $message_ids = [];
        $message_ids[] = $this->send_fake_unread_popup_notification($sender, $recipient, 'Message 1', 1);
        $message_ids[] = $this->send_fake_unread_popup_notification($sender, $recipient, 'Message 2', 2);
        $message_ids[] = $this->send_fake_unread_popup_notification($sender, $recipient, 'Message 3', 3);
        $message_ids[] = $this->send_fake_unread_popup_notification($sender, $recipient, 'Message 4', 4);

        return [$recipient, $message_ids];
    }

    /**
     * Given an array of message numbers, return an input array for the mutation
     *
     * @param array $msgs message numbers ($this->>create_messages() generates [1,2,3, 4])
     * @param $message_ids array of generated message ids
     * @return array $input
     */
    private function standard_input(array $msgs, array $message_ids): array {
        $input = ['input' => ['message_ids' => []]];
        foreach ($msgs as $msgno) {
            if (!empty($message_ids[$msgno - 1])) {
                $input['input']['message_ids'][] = $message_ids[$msgno - 1];
            }
        }
        return $input;
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        [$recipient, $message_ids] = $this->create_messages();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $input = $this->standard_input([3, 4], $message_ids);
        $this->resolve_graphql_mutation('message_popup_mark_messages_read', $input);
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        [$recipient, $message_ids] = $this->create_messages();
        $this->setGuestUser();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Must be an authenticated user)');

        $input = $this->standard_input([3, 4], $message_ids);
        $this->resolve_graphql_mutation('message_popup_mark_messages_read', $input);
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        [$recipient, $message_ids] = $this->create_messages();
        $this->setAdminUser();

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage("Invalid messageid, you don't have permissions to mark this message as read");

        $input = $this->standard_input([3, 4], $message_ids);
        $this->resolve_graphql_mutation('message_popup_mark_messages_read', $input);
    }

    /**
     * Test the results of the query with recipient
     */
    public function test_resolve_recipient_user() {
        [$recipient, $message_ids] = $this->create_messages();

        $this->setUser($recipient);

        try {
            $input = $this->standard_input([3, 4], $message_ids);
            $this->resolve_graphql_mutation('message_popup_mark_messages_read', $input);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the results of the query with recipient trying to resolve an another recipient's message
     */
    public function test_resolve_another_recipient_user() {
        global $DB;
        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient1 = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));
        $recipient2 = $this->getDataGenerator()->create_user(array('firstname' => 'Test3', 'lastname' => 'User3'));

        $message_ids = [];
        $message_ids[] = $this->send_fake_unread_popup_notification($sender, $recipient1, 'Message 1', 1);
        $message_ids[] = $this->send_fake_unread_popup_notification($sender, $recipient2, 'Message 2', 2);
        $message_ids[] = $this->send_fake_unread_popup_notification($sender, $recipient1, 'Message 3', 3);

        // 3 unread notifications from the sender
        $this->assertEquals(
            3,
            $DB->count_records(
                'notifications',
                [
                    'useridfrom' => $sender->id,
                    'timeread' => null
                ]
            )
        );

        $this->setUser($recipient1);

        try {
            $input = $this->standard_input([1, 2, 3], $message_ids);
            $this->resolve_graphql_mutation('message_popup_mark_messages_read', $input);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString("Invalid messageid, you don't have permissions to mark this message as read", $ex->getMessage());
        }

        // Make sure that no messages are marked as read.
        $this->assertEquals(
            3,
            $DB->count_records(
                'notifications',
                [
                    'useridfrom' => $sender->id,
                    'timeread' => null
                ]
            )
        );

        try {
            $message_ids[1] = -42;
            $input = $this->standard_input([1, 2, 3], $message_ids);
            $this->resolve_graphql_mutation('message_popup_mark_messages_read', $input);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString("Invalid messageid, the message doesn't exist", $ex->getMessage());
        }

        // Make sure no messages are marked as read
        $this->assertEquals(
            3,
            $DB->count_records(
                'notifications',
                [
                    'useridfrom' => $sender->id,
                    'timeread' => null
                ]
            )
        );
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        [$recipient, $message_ids] = $this->create_messages();

        $this->setUser($recipient);

        $unread_count = message_api::count_unread_popup_notifications();
        $this->assertEquals(4, $unread_count);

        $input = $this->standard_input([3, 4], $message_ids);
        $result = $this->execute_graphql_operation('message_popup_mark_messages_read', $input);

        $this->assertNotEmpty($result->data);
        $this->assertNotEmpty($result->data['message_popup_mark_messages_read']);
        $this->assertNotEmpty($result->data['message_popup_mark_messages_read']['read_message_ids']);

        $messages = $result->data['message_popup_mark_messages_read']['read_message_ids'];
        $this->assertCount(2, $messages);

        $unread_count = message_api::count_unread_popup_notifications();
        $this->assertEquals(2, $unread_count);
    }
}
