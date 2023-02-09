<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 * @package totara_core
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

class totara_core_session_testcase extends advanced_testcase {
    public function test_totara_queue() {
        global $SESSION;

        $queue_data = array(
            'key0' => 'data0',
            'key1' => array('data1', 'data2'),
        );

        $queue_key_data = array('key0', 'key1');

        // Test totara_queue_append.
        $key = $queue_key_data[0];
        totara_queue_append($key, $queue_data[$key]); // Deprecated.
        self::assertDebuggingCalled('totara_queue_append() has been deprecated due to disuse.');
        $this->assertEquals($SESSION->totara_queue[$key][0], $queue_data[$key]);

        $key = $queue_key_data[1];
        totara_queue_append($key, $queue_data[$key][0]); // Deprecated.
        self::assertDebuggingCalled('totara_queue_append() has been deprecated due to disuse.');
        totara_queue_append($key, $queue_data[$key][1]); // Deprecated.
        self::assertDebuggingCalled('totara_queue_append() has been deprecated due to disuse.');
        $this->assertSame($SESSION->totara_queue[$key], $queue_data[$key]);

        // Test totara_queue_shift.
        $key = $queue_key_data[0];
        $this->assertEquals(totara_queue_shift($key), $queue_data[$key]); // Deprecated.
        self::assertDebuggingCalled('totara_queue_shift() has been deprecated due to disuse.');
        $this->assertNull(totara_queue_shift($key)); // Deprecated.
        self::assertDebuggingCalled('totara_queue_shift() has been deprecated due to disuse.');

        $key = $queue_key_data[1];
        $this->assertSame(totara_queue_shift($key, true), $queue_data[$key]); // Deprecated.
        self::assertDebuggingCalled('totara_queue_shift() has been deprecated due to disuse.');
        $this->assertEquals(totara_queue_shift($key, true), array()); // Deprecated.
        self::assertDebuggingCalled('totara_queue_shift() has been deprecated due to disuse.');
    }

    /**
     * It should convert a templatable to the legacy array structure.
     */
    public function test_totara_convert_notification_to_legacy_array() {
        $notification = new \core\output\notification('Foo');
        $expected = [
            'message' => 'Foo',
            'class' => \core\output\notification::NOTIFY_ERROR,
        ];

        $this->assertEquals($expected, totara_convert_notification_to_legacy_array($notification)); // Deprecated.

        $notification = (new \core\output\notification('Foo', \core\output\notification::NOTIFY_SUCCESS))
            ->set_extra_classes(['one', 'two', 'three']);
        $expected = [
            'message' => 'Foo',
            'class' => \core\output\notification::NOTIFY_SUCCESS . ' one two three',
        ];

        $this->assertEquals($expected, totara_convert_notification_to_legacy_array($notification)); // Deprecated.
    }

    public function test_totara_notifications() {

        // Test notifications without options.
        totara_set_notification('Foo'); // Deprecated.
        self::assertDebuggingCalled([
            'totara_set_notification() has been deprecated, please use redirect() or \core\notification::*() instead.',
            'totara_queue_append() has been deprecated due to disuse.'
            ]);
        totara_set_notification('Bar', null, ['class' => 'foo notifysuccess']); // Deprecated.
        self::assertDebuggingCalled([
            'totara_set_notification() has been deprecated, please use redirect() or \core\notification::*() instead.',
            'totara_queue_append() has been deprecated due to disuse.'
        ]);
        totara_set_notification('Baz', null, ['class' => 'foo bar notifymessage baz']); // Deprecated.
        self::assertDebuggingCalled([
            'totara_set_notification() has been deprecated, please use redirect() or \core\notification::*() instead.',
            'totara_queue_append() has been deprecated due to disuse.'
        ]);
        $expected = [];
        $expected[] = [
            'class' => \core\output\notification::NOTIFY_ERROR,
            'message' => 'Foo',
        ];
        $expected[] = [
            'class' => \core\output\notification::NOTIFY_SUCCESS . ' foo',
            'message' => 'Bar',
        ];
        $expected[] = [
            'class' => \core\output\notification::NOTIFY_INFO . ' foo bar baz',
            'message' => 'Baz',
        ];
        self::assertEquals($expected, totara_get_notifications()); // Deprecated.
        self::assertDebuggingCalled('totara_get_notifications() has been deprecated, please use \core\notification::fetch() instead.');

        // Test notifications with arbitrary options.
        totara_set_notification('What larks, Pip', null, ['option1' => 7]); // Deprecated.
        self::assertDebuggingCalled([
            'totara_set_notification() has been deprecated, please use redirect() or \core\notification::*() instead.',
            'totara_queue_append() has been deprecated due to disuse.'
        ]);
        totara_set_notification('Another message', null, ['class' => 'notifymessage', 'foo' => 'This is an option!', 'bar' => 24]); // Deprecated.
        self::assertDebuggingCalled([
            'totara_set_notification() has been deprecated, please use redirect() or \core\notification::*() instead.',
            'totara_queue_append() has been deprecated due to disuse.'
        ]);
        $expected = [];
        $expected[] = [
            'class' => \core\output\notification::NOTIFY_ERROR,
            'message' => 'What larks, Pip',
            'option1' => 7,
        ];
        $expected[] = [
            'class' => \core\output\notification::NOTIFY_INFO,
            'message' => 'Another message',
            'foo' => 'This is an option!',
            'bar' => 24,
        ];
        self::assertEquals($expected, totara_get_notifications()); // Deprecated.
        $messages = self::getDebuggingMessages();
        self::assertCount(3, $messages);
        self::resetDebugging();
        $expected = [
            'totara_get_notifications() has been deprecated, please use \core\notification::fetch() instead.',
            'The use of custom data in notifications has been deprecated since Totara 13 and should no longer be used.',
            'The use of custom data in notifications has been deprecated since Totara 13 and should no longer be used.',
        ];
        $actual = [];
        foreach ($messages as $message) {
            $actual[] = $message->message;
        }
        self::assertSame($expected, $actual);
    }

}
