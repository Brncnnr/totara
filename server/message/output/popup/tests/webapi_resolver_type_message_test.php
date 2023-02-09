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

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;
use message_popup\api;

global $CFG;

require_once($CFG->dirroot . '/message/tests/messagelib_test.php');
require_once($CFG->dirroot . '/message/output/popup/tests/base.php');

/**
 * Tests the message_popup message type resolver.
 */
class nessage_popup_webapi_resolver_type_message_testcase extends advanced_testcase {

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

    private function resolve($field, $popup_message, array $args = []) {
        return $this->resolve_graphql_type('message_popup_message', $field, $popup_message, $args);
    }

    /**
     * Create some messages for testing.
     * @return stdClass user
     */
    private function create_messages() {
        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        $this->send_fake_read_popup_notification($sender, $recipient, "Message 1", 1);
        $this->send_fake_unread_popup_notification($sender, $recipient, 'Message 2', 2);

        return $recipient;
    }

    /**
     * Test the message type resolver for the id field
     */
    public function test_resolve_id() {
        global $DB;
        $recipient = $this->create_messages();
        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message = $messages[0];

        $value = $this->resolve('id', $message);
        $this->assertEquals($message->id, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the message type resolver for the subject field
     */
    public function test_resolve_subject() {
        global $DB;
        $recipient = $this->create_messages();
        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message = $messages[0];
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('subject', $message, ['format' => 'FOO']);
            $this->fail('Expected failure on unimplemented $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('subject', $message, ['format' => $format]);
            $this->assertEquals($message->subject, $value);
            $this->assertTrue(is_string($value));
        }
    }

    /**
     * Test the message type resolver for the fullmessage field
     */
    public function test_resolve_fullmessage() {
        global $DB;
        $recipient = $this->create_messages();
        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message = $messages[0];
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('fullmessage', $message, ['format' => 'FOO']);
            $this->fail('Expected failure on unimplemented $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('fullmessage', $message, ['format' => $format]);
            $this->assertEquals($message->fullmessage, $value);
            $this->assertTrue(is_string($value));
        }
    }

    /**
     * Test the message type resolver for the fullmessagehtml field
     */
    public function test_resolve_fullmessagehtml() {
        global $DB;

        // Set up a couple of more detailed messages than the other tests.
        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        $message1 = "<h1>Message 1</h1><p>Once upon a time<br />In a land <strong>far far</strong> away<br />stuff happened</p>";
        $mid = $this->send_fake_read_popup_notification($sender, $recipient, $message1, 1);

        $message2 = "Message 2 is very simple.";
        $this->send_fake_unread_popup_notification($sender, $recipient, $message2, 2);

        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message = $messages[0];

        // Test an unexpected format.
        try {
            $value = $this->resolve('fullmessagehtml', $message, ['format' => 'FOO']);
            $this->fail('Expected failure on unimplemented $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        // Test the expected formats.
        $value = $this->resolve('fullmessagehtml', $message, ['format' => format::FORMAT_HTML]);
        $this->assertIsString($value);
        $this->assertEquals($message1, $value);

        $value = $this->resolve('fullmessagehtml', $message, ['format' => format::FORMAT_PLAIN]);
        $this->assertIsString($value);
        $expected = "MESSAGE 1\n\nOnce upon a time\nIn a land FAR FAR away\nstuff happened\n";
        $this->assertEquals($expected, $value);

        $value = $this->resolve('fullmessagehtml', $message, ['format' => format::FORMAT_RAW]);
        $this->assertIsString($value);
        $this->assertEquals($message1, $value);

        // Test a failed auth for format raw.
        $sysctx = context_system::instance();
        $m_roleid = create_role('Mobile role', 'mobrole', 'Mobile role description');
        assign_capability('moodle/site:sendmessage', CAP_PROHIBIT, $m_roleid, $sysctx);
        role_assign($m_roleid, $recipient->id, $sysctx->id);
        $this->assertFalse(has_capability('moodle/site:sendmessage', \context_user::instance($recipient->id)));

        $value = $this->resolve('fullmessagehtml', $message, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);
    }

    /**
     * Test the message type resolver for the fullmessageformat field
     */
    public function test_resolve_fullmessageformat() {
        global $DB;
        $recipient = $this->create_messages();
        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message = $messages[0];

        $value = $this->resolve('fullmessageformat', $message);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the message type resolver for the contexturl field
     */
    public function test_resolve_contexturl() {
        global $DB;
        $recipient = $this->create_messages();
        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message = $messages[0];

        $value = $this->resolve('contexturl', $message, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals($message->contexturl, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the message type resolver for the timecreated field
     */
    public function test_resolve_timecreated() {
        global $DB;
        $recipient = $this->create_messages();
        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message = $messages[0];

        $value = $this->resolve('timecreated', $message, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($message->timecreated, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the message type resolver for the isread field
     */
    public function test_resolve_isread() {
        global $DB;
        $recipient = $this->create_messages();
        $this->setUser($recipient);
        $messages = api::get_popup_notifications($recipient->id, 'ASC');
        $message1 = $messages[0];
        $message2 = $messages[1];

        $value = $this->resolve('isread', $message1);
        $this->assertEquals(true, $value);
        $this->assertTrue(is_bool($value));

        $value = $this->resolve('isread', $message2);
        $this->assertEquals(false, $value);
        $this->assertTrue(is_bool($value));
    }
}
