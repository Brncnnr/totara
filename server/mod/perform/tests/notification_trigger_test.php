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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\notification\trigger;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @coversDefaultClass \mod_perform\notification\trigger
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_trigger_testcase extends mod_perform_notification_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->mock_loader(null);
    }

    /**
     * @cover ::are_triggers_available
     */
    public function test_are_triggers_available() {
        $this->assertFalse((new trigger('mock_one'))->are_triggers_available());
        $this->assertTrue((new trigger('mock_two'))->are_triggers_available());
        $this->assertTrue((new trigger('mock_three'))->are_triggers_available());
    }

    /**
     * @cover ::translate_outgoing
     */
    public function test_translate_outgoing() {
        $input = [259200, 86400, 345600];
        $this->assertEquals([], (new trigger('mock_one'))->translate_outgoing($input));
        $this->assertEquals([3, 1, 4], (new trigger('mock_two'))->translate_outgoing($input));
        $this->assertEquals([3, 1, 4], (new trigger('mock_three'))->translate_outgoing($input));
    }

    /**
     * @cover ::translate_incoming
     */
    public function test_translate_incoming() {
        $this->assertEmpty((new trigger('mock_one'))->translate_incoming([]));
        $this->assertEmpty((new trigger('mock_two'))->translate_incoming([]));
        $this->assertEmpty((new trigger('mock_three'))->translate_incoming([]));
        try {
            (new trigger('mock_one'))->translate_incoming([42]);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
        }

        foreach (['mock_two', 'mock_three'] as $class_key) {
            $trigger = new trigger($class_key);
            $this->assertEquals([86400, 259200, 345600], $trigger->translate_incoming([3, 1, 4]));
            try {
                $trigger->translate_incoming([0]);
                $this->fail('invalid_parameter_exception expected');
            } catch (invalid_parameter_exception $ex) {
            }
            try {
                $trigger->translate_incoming([-1]);
                $this->fail('invalid_parameter_exception expected');
            } catch (invalid_parameter_exception $ex) {
            }
            try {
                $trigger->translate_incoming([366]);
                $this->fail('invalid_parameter_exception expected');
            } catch (invalid_parameter_exception $ex) {
            }
            try {
                $trigger->translate_incoming([9, 1, 1]);
                $this->fail('invalid_parameter_exception expected');
            } catch (invalid_parameter_exception $ex) {
            }
        }
    }
}
