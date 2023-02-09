<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_event
 */
defined('MOODLE_INTERNAL') || die();

use core_tests\event\unittest_executed;
use core\event\manager;

class core_event_fail_observer_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public static function fail_observer(): void {
        throw new \coding_exception("This is supposed to be failing");
    }

    /**
     * @return void
     */
    public function test_array_callable(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/tests/fixtures/event_fixtures.php");

        $observers = [
            [
                'eventname' => unittest_executed::class,
                'callback' => [static::class, 'fail_observer'],
            ]
        ];

        $result = manager::phpunit_replace_observers($observers);
        $this->assertCount(1, $result);

        $event = unittest_executed::create(
            [
                'context' => context_system::instance(),
                'other' => [
                    'sample' => "hello world"
                ]
            ]
        );

        $event->trigger();
        $debugs = $this->getDebuggingMessages();
        $this->assertDebuggingCalled();

        $this->assertCount(1, $debugs);
        $debug = reset($debugs);
        $message = $debug->message;

        $cls = static::class;
        $this->assertStringContainsString(
            "{$cls}::fail_observer",
            $message,
            "Expect the observer callable to be crafted as a string"
        );
    }
}