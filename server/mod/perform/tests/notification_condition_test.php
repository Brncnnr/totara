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

use mod_perform\notification\conditions\after_midnight;
use mod_perform\notification\conditions\days_after;
use mod_perform\notification\conditions\days_before;
use mod_perform\notification\factory;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_condition_testcase extends mod_perform_notification_testcase {
    public function data_days_before_after(): array {
        return [
            'days_before' => [
                days_before::class,
                [3 * DAYSECS, 6 * DAYSECS, 7 * DAYSECS],
                8 * DAYSECS,
            ],
            'days_after' => [
                days_after::class,
                [1 * DAYSECS, 2 * DAYSECS, 5 * DAYSECS],
                0,
            ],
        ];
    }

    /**
     * Test the days_before and days_after conditions.
     *
     * @param string $class_name
     * @param array $triggers
     * @param integer $base_time_offset
     * @dataProvider data_days_before_after
     */
    public function test_days_before_after(string $class_name, array $triggers, int $base_time_offset) {
        $base_time = time() + $base_time_offset;
        $clock = factory::create_clock();
        $condition = new $class_name($clock, $triggers, 0);
        $this->assertFalse($condition->pass($base_time));

        // Loop  Bias   <---- 8 ----><==== 7 ====><==== 6 ====><---- 5 ----><---- 4 ----><==== 3 ====><---- 2 ----><---- 1 ---->
        //              <---- 0 ----><==== 1 ====><==== 2 ====><---- 3 ----><---- 4 ----><==== 5 ====><---- 6 ----><---- 7 ---->
        //  0   0d 17h           *
        //  1   1d 10h                    *
        //  2   2d  3h                              *
        //  3   2d 20h                                      *
        //  4   3d 13h                                                *
        //  5   4d  6h                                                        *
        //  6   4d 23h                                                                *
        //  7   5d 16h                                                                           *
        //  8   6d  9h                                                                                    *
        //  9   7d  2h                                                                                              *
        $expectation = [         false,  true,      true,    false,   false,  false,   false,    true,    false,   false];
        $bias = 0;
        $last_run_time = 0;
        for ($i = 0; $i < 10; $i++) {
            $clock = factory::create_clock_with_time_offset(17 * HOURSECS);
            $bias += 17 * HOURSECS;
            $condition = new $class_name($clock, $triggers, $last_run_time);
            $this->assertEquals($expectation[$i], $condition->pass($base_time), sprintf('Failure at #%d (%dd %02dh)', $i, $bias / DAYSECS, ($bias % DAYSECS) / HOURSECS));
            $last_run_time = $clock->get_time();
        }
    }

    /**
     * Test the after_midnight condition at 6am.
     */
    public function test_after_midnight_at() {
        $tz = \core_date::get_server_timezone_object();
        $midnight = (new DateTime('midnight', $tz))->getTimestamp();
        $six_am = (new DateTime('6am today', $tz))->getTimestamp();
        //               -4 ,   0 ,  +4 ,  +8 ,  +12,  +16,  +20,  +24 ,  +28
        $expectation = [true, true, true, true, true, true, true, false, false];
        for ($i = 0; $i < 9; $i++) {
            $time = ($i - 1) * 4 * HOURSECS + $midnight;
            $clock = new mod_perform_mock_clock($six_am);
            $condition = new after_midnight($clock, [], 0);
            $this->assertEquals($expectation[$i], $condition->pass($time), sprintf('Failure at #%d (%02dh)', $i, (int)(($time - $midnight) / HOURSECS)));
        }
    }

    /**
     * Test the after_midnight condition every 4 hours between 10pm yesterday and 4am tomorrow where the base time is 6pm today.
     */
    public function test_after_midnight_for() {
        $tz = \core_date::get_server_timezone_object();
        $midnight = (new DateTime('midnight', $tz))->getTimestamp();
        $six_am = (new DateTime('today 6am', $tz))->getTimestamp();
        $two_pm = (new DateTime('today 2pm', $tz))->getTimestamp();
        $yesterday = (new DateTime('yesterday 7pm', $tz))->getTimestamp();
        $tomorrow = (new DateTime('tomorrow 3am', $tz))->getTimestamp();
        //                -4 ,   0 ,  +4 ,  +8 ,  +12,  +16,  +20,  +24,  +28
        $expectation = [false, true, true, true, true, true, true, true, true];
        for ($i = 0; $i < 9; $i++) {
            $time = ($i - 1) * 4 * HOURSECS + $midnight;
            $clock = new mod_perform_mock_clock($time);
            $condition = new after_midnight($clock, [], 0);
            $this->assertEquals($expectation[$i], $condition->pass($six_am), sprintf('Failure at #%d (%02dh)', $i, (int)(($time - $midnight) / HOURSECS)));
            $condition = new after_midnight($clock, [], $yesterday);
            $this->assertEquals($expectation[$i], $condition->pass($six_am), sprintf('Failure at #%d (%02dh)', $i, (int)(($time - $midnight) / HOURSECS)));
            $condition = new after_midnight($clock, [], $two_pm);
            $this->assertFalse($condition->pass($six_am), sprintf('Failure at #%d (%02dh)', $i, (int)(($time - $midnight) / HOURSECS)));
            $condition = new after_midnight($clock, [], $tomorrow);
            $this->assertFalse($condition->pass($six_am), sprintf('Failure at #%d (%02dh)', $i, (int)(($time - $midnight) / HOURSECS)));
        }
    }

    public function test_get_last_midnight() {
        $tz = \core_date::get_server_timezone_object();
        $time = new DateTime('now', $tz);
        $midnight = (clone $time)->modify('midnight');
        $offsets = [-366, -365, -20, -3, -1, 0, +1, +7, +77, +365, +366];
        foreach ($offsets as $i => $offset) {
            $expected = $midnight->getTimestamp() + $offset * DAYSECS;
            $this->assertEquals($expected, after_midnight::get_last_midnight($time->getTimestamp() + $offset * DAYSECS), sprintf('Failure at #%d (%d day)', $i, $offset));
        }
        $time = new DateTime('6am', $tz);
        $offsets = [
            -23 => (clone $midnight)->modify('yesterday'), // 6am - 23 hrs = 7am yesterday
            -7 => (clone $midnight)->modify('yesterday'), // 6am - 7 hrs = 11pm yesterday
            -6 => $midnight,
            -3 => $midnight,
            -1 => $midnight,
            +1 => $midnight,
            +5 => $midnight,
            +17 => $midnight,
            +18 => (clone $midnight)->modify('tomorrow'), // 6am + 18 hrs = 12am tomorrow
            +23 => (clone $midnight)->modify('tomorrow'), // 6am + 23 hrs = 5am tomorrow
        ];
        foreach ($offsets as $offset => $expected) {
            $this->assertEquals($expected->getTimestamp(), after_midnight::get_last_midnight($time->getTimestamp() + $offset * HOURSECS), sprintf('Failure at #%d (%d hour)', $i, $offset));
        }
    }
}
