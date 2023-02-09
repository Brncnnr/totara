<?php
/**
 * This file is part of Totara Core
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
 * @package core_message
 */
use core_phpunit\testcase;

class core_message_post_message_testcase extends testcase {
    /**
     * @var stdClass|null
     */
    private $user_one;

    /**
     * @var stdClass|null
     */
    private $user_two;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/message/externallib.php");

        $generator = self::getDataGenerator();
        $this->user_one = $generator->create_user(["firstname" => "User", "lastname" => "One"]);
        $this->user_two = $generator->create_user(["firstname" => "User", "lastname" => "Two"]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->user_one = null;
        $this->user_two = null;
    }

    /**
     * @return void
     */
    public function test_post_message_with_unclosed_tags(): void {
        // Constructing a message to send to user.
        $post_messages = [
            [
                "touserid" => $this->user_two->id,
                "text" => /** @lang text */"<h1>Hello world</h1><!--",
                "textformat" => FORMAT_MOODLE,
            ]
        ];

        // Set the user in the session, so that we can send the message.
        self::setUser($this->user_one);

        // Note: we use email sink, because it can only be reproduced via email.
        $sink = self::redirectEmails();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        // Send the message to user two.
        core_message_external::send_instant_messages($post_messages);

        $messages = $sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $message = reset($messages);
        self::assertIsObject($message);

        self::assertObjectHasAttribute("from", $message);
        self::assertObjectHasAttribute("to", $message);

        self::assertEquals(core_user::get_noreply_user()->email, $message->from);
        self::assertEquals($this->user_two->email, $message->to);

        self::assertObjectHasAttribute("fromname", $message);
        self::assertEquals(fullname($this->user_one), $message->fromname);

        self::assertObjectHasAttribute("toname", $message);
        self::assertEquals(fullname($this->user_two), $message->toname);

        self::assertObjectHasAttribute("body", $message);
        self::assertStringContainsString(
            /** @lang text */"<h1>Hello world</h1>",
            $message->body
        );

        self::assertStringNotContainsString("<!--", $message->body);
    }

    /**
     * @return void
     */
    public function test_post_message_with_xss(): void {
        // Constructing a message send to user.
        $post_messages = [
            [
                "touserid" => $this->user_two->id,
                "text" => /** @lang text */ "<script>alert('hello world')</script>hi there",
                "textformat" => FORMAT_MOODLE
            ]
        ];

        self::setUser($this->user_one);

        // Note: we use email sink, because it can only be reproduced via email.
        $sink = self::redirectEmails();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        core_message_external::send_instant_messages($post_messages);
        $messages = $sink->get_messages();

        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $message = reset($messages);
        self::assertIsObject($message);

        self::assertObjectHasAttribute("from", $message);
        self::assertObjectHasAttribute("to", $message);

        self::assertEquals(core_user::get_noreply_user()->email, $message->from);
        self::assertEquals($this->user_two->email, $message->to);

        self::assertObjectHasAttribute("fromname", $message);
        self::assertEquals(fullname($this->user_one), $message->fromname);

        self::assertObjectHasAttribute("toname", $message);
        self::assertEquals(fullname($this->user_two), $message->toname);

        self::assertObjectHasAttribute("body", $message);
        self::assertStringNotContainsString(
            /** @lang text */ "<script>alert('hello world')</script>",
            $message->body
        );

        self::assertStringNotContainsString("alert('hello world')", $message->body);
        self::assertStringContainsString("hi there", $message->body);
    }

    /**
     * @return void
     */
    public function test_post_message_with_normal_text(): void {
        // Constructing a message send to user.
        $post_messages = [
            [
                "touserid" => $this->user_two->id,
                "text" => "hi there",
                "textformat" => FORMAT_MOODLE
            ]
        ];

        self::setUser($this->user_one);

        // Note: we use email sink, because it can only be reproduced via email.
        $sink = self::redirectEmails();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        core_message_external::send_instant_messages($post_messages);
        $messages = $sink->get_messages();

        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $message = reset($messages);
        self::assertIsObject($message);

        self::assertObjectHasAttribute("from", $message);
        self::assertObjectHasAttribute("to", $message);

        self::assertEquals(core_user::get_noreply_user()->email, $message->from);
        self::assertEquals($this->user_two->email, $message->to);

        self::assertObjectHasAttribute("fromname", $message);
        self::assertEquals(fullname($this->user_one), $message->fromname);

        self::assertObjectHasAttribute("toname", $message);
        self::assertEquals(fullname($this->user_two), $message->toname);

        self::assertObjectHasAttribute("body", $message);
        self::assertStringContainsString("hi there", $message->body);
    }
}