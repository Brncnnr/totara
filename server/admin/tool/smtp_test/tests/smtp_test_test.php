<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totara.com>
 * @package tool_smtp_test
 */

defined('MOODLE_INTERNAL') || die();

use tool_smtp_test\smtp_test;

/**
 * Class tool_smtp_test_testcase
 *
 * Tests the tool_smtp_test::send_test_email() method
 *
 * @group tool_smtp_test
 */
class tool_smtp_test_testcase extends advanced_testcase {

    public function test_send_test_email() {
        $email_sink = $this->redirectEmails();
        $subject = 'PHPUnit test email';
        $message = 'This is a PHPUnit test email.';

        // Test happy path
        $user = $this->getDataGenerator()->create_user();
        $emails = $email_sink->get_messages();
        $this->assertCount(0, $emails);
        $result = smtp_test::send_test_email($user->email, $subject, $message, $user);
        $this->assertTrue($result);
        $emails = $email_sink->get_messages();
        $this->assertCount(1, $emails);
        $this->assertEquals('PHPUnit test email', $emails[0]->subject);
        $this->assertEquals($user->email, $emails[0]->to);
        $email_sink->clear();

        // Test without user
        $result = smtp_test::send_test_email($user->email, $subject, $message);
        $this->assertTrue($result);
        $emails = $email_sink->get_messages();
        $this->assertCount(1, $emails);
        $this->assertEquals('PHPUnit test email', $emails[0]->subject);
        $this->assertEquals($user->email, $emails[0]->to);
        $email_sink->clear();

        // Test to and user don't match
        $result = smtp_test::send_test_email('nobody@example.com', $subject, $message, $user);
        $this->assertDebuggingCalled(['Not a Totara user.', 'Email address and user record do not match.']);
        $this->assertFalse($result);
        $emails = $email_sink->get_messages();
        $this->assertCount(0, $emails);
    }
}