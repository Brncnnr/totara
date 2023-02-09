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
use message_popup\api;

global $CFG;

require_once($CFG->dirroot . '/message/tests/messagelib_test.php');
require_once($CFG->dirroot . '/message/output/popup/tests/base.php');

/**
 * Tests the message_popup messages query resolver
 */
class message_popup_webapi_resolver_query_messages_testcase extends advanced_testcase {

    use webapi_phpunit_helper;
    use message_popup_test_helper;

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp(): void {
        $this->messagesink = $this->redirectMessages();
    }

    /**
     * Create some messages for testing.
     * @return stdClass user
     */
    private function create_messages() {
        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        $this->send_fake_read_popup_notification($sender, $recipient, 'Message 1', 1);
        $this->send_fake_unread_popup_notification($sender, $recipient, 'Message 2', 2);
        $this->send_fake_unread_popup_notification($sender, $recipient, 'Message 3', 3);
        $this->send_fake_read_popup_notification($sender, $recipient, 'Message 4', 4);

        return $recipient;
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        $recipient = $this->create_messages();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_query('message_popup_messages');
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        $recipient = $this->create_messages();
        $this->setGuestUser();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Must be an authenticated user)');

        $this->resolve_graphql_query('message_popup_messages');
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        $recipient = $this->create_messages();
        $this->setAdminUser();

        // No messages were sent to admin.
        $messages = $this->resolve_graphql_query('message_popup_messages');
        $this->assertEquals(count($messages), 0);
    }

    /**
     * Test the results of the query with recipient
     */
    public function test_resolve_messages() {
        $recipient = $this->create_messages();

        $this->setUser($recipient);

        try {
            $messages = $this->resolve_graphql_query('message_popup_messages');

            // Do some checks on the messages to make sure they are what we're expecting
            $this->assertEquals(count($messages), 4);
            $this->assertEquals($messages[0]->fullmessage, 'Message 4');
            $this->assertEquals($messages[1]->fullmessage, 'Message 3');
            $this->assertEquals($messages[2]->fullmessage, 'Message 2');
            $this->assertEquals($messages[3]->fullmessage, 'Message 1');
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        $recipient = $this->create_messages();

        $this->setUser($recipient);

        $result = $this->execute_graphql_operation('message_popup_messages', []);

        $this->assertNotEmpty($result->data);
        $this->assertNotEmpty($result->data['message_popup_messages']);

        $messages = $result->data['message_popup_messages'];
        $this->assertCount(4, $messages);

        $message0 = $messages[0];
        $this->assertEquals('No subject', $message0['subject']);
        $this->assertEquals('Message 4', $message0['fullmessage']);
        $this->assertEquals('HTML', $message0['fullmessageformat']);
        $this->assertEquals('https://www.totaralearning.com/', $message0['contexturl']);
        $this->assertEquals('4', $message0['timecreated']);
        $this->assertEquals(true, $message0['isread']);

        $message1 = $messages[1];
        $this->assertEquals('No subject', $message1['subject']);
        $this->assertEquals('Message 3', $message1['fullmessage']);
        $this->assertEquals('HTML', $message1['fullmessageformat']);
        $this->assertEquals('https://www.totaralearning.com/', $message1['contexturl']);
        $this->assertEquals('3', $message1['timecreated']);
        $this->assertEquals(false, $message1['isread']);
    }
}
