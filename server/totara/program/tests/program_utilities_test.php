<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

use \totara_program\utils;

class totara_program_program_utilities_testcase extends advanced_testcase {

    /**
     * Test that various durations output the expected number of units
     * and period values in get_duration_num_and_period.
     *
     * @throws coding_exception
     */
    public function test_get_duration_num_and_period_values() {

        // Duration of zero should mean there is no minimum.
        $returned = utils::get_duration_num_and_period(0);
        $this->assertEquals(0, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_NOMINIMUM, $returned->period);
        $this->assertEquals('nominimum', $returned->periodkey);

        $threeyears = 3 * YEARSECS;
        $returned = utils::get_duration_num_and_period($threeyears);
        $this->assertEquals(3, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_YEARS, $returned->period);
        $this->assertEquals('years', $returned->periodkey);

        $sevenmonths = 7 * utils::DURATION_MONTH;
        $returned = utils::get_duration_num_and_period($sevenmonths);
        $this->assertEquals(7, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_MONTHS, $returned->period);
        $this->assertEquals('months', $returned->periodkey);

        $fourweeks = 4 * WEEKSECS;
        $returned = utils::get_duration_num_and_period($fourweeks);
        $this->assertEquals(4, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_WEEKS, $returned->period);
        $this->assertEquals('weeks', $returned->periodkey);

        $twodays = 2 * DAYSECS;
        $returned = utils::get_duration_num_and_period($twodays);
        $this->assertEquals(2, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_DAYS, $returned->period);
        $this->assertEquals('days', $returned->periodkey);

        $fifteenhours = 15 * HOURSECS;
        $returned = utils::get_duration_num_and_period($fifteenhours);
        $this->assertEquals(15, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_HOURS, $returned->period);
        $this->assertEquals('hours', $returned->periodkey);
    }

    /**
     * Test that duration values that involve unsupported units
     * thrown an exception in get_duration_num_and_period.
     */
    public function test_get_duration_num_and_period_exception() {
        $minutes = 1 * DAYSECS + 12 * MINSECS;
        try {
            $returned = utils::get_duration_num_and_period($minutes);
            $this->fail('Unsupported units should have thrown an exception');
        } catch (coding_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Unrecognised datetime', $e->getMessage());
        }

        try {
            $returned = utils::get_duration_num_and_period(1);
            $this->fail('Unsupported units should have thrown an exception');
        } catch (coding_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Unrecognised datetime', $e->getMessage());
        }
    }

    /**
     * Test that when values are supplied that are divisible by small and
     * large units, that the larger units are what is returned
     * in get_duration_num_and_period.
     *
     * @throws moodle_exception
     */
    public function test_get_duration_num_and_period_order() {
        // If it is divisible by years and days, years should be output.
        $threesixtyfivedays = 365 * DAYSECS;
        $returned = utils::get_duration_num_and_period($threesixtyfivedays);
        $this->assertEquals(1, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_YEARS, $returned->period);
        $this->assertEquals('years', $returned->periodkey);

        // If it is divisable by weeks and days, weeks should be output.
        $sevendays = 7 * DAYSECS;
        $returned = utils::get_duration_num_and_period($sevendays);
        $this->assertEquals(1, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_WEEKS, $returned->period);
        $this->assertEquals('weeks', $returned->periodkey);

        // If it is divisable by days and hours, days should be output.
        $twentyfourhours = 24 * HOURSECS;
        $returned = utils::get_duration_num_and_period($twentyfourhours);
        $this->assertEquals(1, $returned->num);
        $this->assertEquals(utils::TIME_SELECTOR_DAYS, $returned->period);
        $this->assertEquals('days', $returned->periodkey);
    }
}
