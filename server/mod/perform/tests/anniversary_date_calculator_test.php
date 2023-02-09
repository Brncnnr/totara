<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\dates\anniversary_date_calculator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group perform
 */
class mod_perform_anniversary_date_calculator_testcase extends advanced_testcase {

    /**
     * @dataProvider calculate_provider
     * @param string $date
     * @param string $now
     * @param string $expected
     */
    public function test_calculations(string $date, string $now, string $expected): void {
        $actual = (new anniversary_date_calculator())->calculate(
            strtotime($date . 'T00:00:00 UTC'),
            strtotime($now . 'T00:00:00 UTC')
        );

        $actual_formatted = (new DateTime("@{$actual}"))->format('Y-m-d');
        self::assertEquals($expected, $actual_formatted);
    }

    /**
     * @dataProvider calculate_provider
     * @param string $date
     * @param string $now
     * @param string $expected
     */
    public function test_calculations_should_ignore_time_component(string $date, string $now, string $expected): void {
        $actual = (new anniversary_date_calculator())->calculate(
            strtotime($date . 'T12:00:00 UTC'),
            strtotime($now . 'T06:00:00 UTC')
        );

        $actual_formatted = (new DateTime("@{$actual}"))->format('Y-m-d');
        self::assertEquals($expected, $actual_formatted, 'Failed with reference time before now time');

        // Run again with flipped times.
        $actual = (new anniversary_date_calculator())->calculate(
            strtotime($date . 'T06:00:00 UTC'),
            strtotime($now . 'T12:00:00 UTC')
        );

        $actual_formatted = (new DateTime("@{$actual}"))->format('Y-m-d');
        self::assertEquals($expected, $actual_formatted, 'Failed with reference time after now time');
    }
    public function calculate_provider(): array {
        return [                                            //   ref date,     now date,     expected
            'Exactly a year before now' =>                  ['2019-06-06', '2020-06-06', '2020-06-06'],
            'Reference date is 366 days ago' =>             ['2019-06-06', '2020-06-07', '2021-06-06'],
            'Reference date is 364 days ago' =>             ['2019-06-06', '2020-06-05', '2020-06-06'],
            'Reference date is now' =>                      ['2020-06-06', '2020-06-06', '2020-06-06'],
            'Reference date is one day after now' =>        ['2020-06-07', '2020-06-06', '2020-06-07'],
            'Reference date is several years after now' =>  ['2022-06-06', '2020-06-06', '2022-06-06'],
            'Reference date is several years before now' => ['2012-06-06', '2020-06-06', '2020-06-06'],
            'Now is on a leap year day' =>                  ['2020-02-29', '2021-03-01', '2021-03-01'],
        ];
    }

}