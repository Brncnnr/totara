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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

class block_current_learning_helper_testcase extends advanced_testcase {

    /**
     * Test failure scenarios of block_current_learning\helper::get_duedate_state.
     */
    public function test_get_duedate_state_throws_exception() {
        // alertperiod must be less than or equal to warningperiod.
        $config = new stdClass();
        $config->alertperiod = DAYSECS * 20;
        $config->warningperiod = DAYSECS * 10;
        try {
            \block_current_learning\helper::get_duedate_state(time() + YEARSECS, $config, time());
            $this->fail('coding_exception expected when alertperiod > warningperiod');
        } catch (\coding_exception $ex) {
            $this->assertStringContainsString('Warning period cannot be before the alert period', $ex->getMessage());
        }
    }

    /**
     * Data provider for test_get_duedate_state_returns_correct_states.
     *
     * @return array of [ alertperiod, warningperiod, expectation_for_timeframe_1_to_7 ]
     */
    public function data_get_duedate_state_returns_correct_states() {
        return [
            [
                WEEKSECS,                       // alert = 1 week (default)
                30 * DAYSECS,                   // warning = 30 days (default)
                [
                    ['label-danger', true],     // (1)
                    ['label-danger', true],     // (2)
                    ['label-danger', false],    // (3)
                    ['label-danger', false],    // (4)
                    ['label-warning', false],   // (5)
                    ['label-warning', false],   // (6)
                    ['label-info', false],      // (7)
                ]
            ],
            [
                10 * DAYSECS,                   // alert = 10 days
                10 * DAYSECS,                   // warning = alert
                [
                    ['label-danger', true],     // (1)
                    ['label-danger', true],     // (2)
                    ['label-danger', false],    // (3)
                    ['label-danger', false],    // (4)
                    ['label-danger', false],    // (5)
                    ['label-danger', false],    // (6)
                    ['label-info', false],      // (7)
                ]
            ],
            [
                0,                              // alert = 0
                20 * DAYSECS,                   // warning = 20 days
                [
                    ['label-danger', true],     // (1)
                    ['label-danger', true],     // (2)
                    ['label-danger', true],     // (3)
                    ['label-danger', true],     // (4)
                    ['label-warning', false],   // (5)
                    ['label-warning', false],   // (6)
                    ['label-info', false],      // (7)
                ]
            ],
            [
                0,                              // alert = 0
                0,                              // warning = 0
                [
                    ['label-danger', true],     // (1)
                    ['label-danger', true],     // (2)
                    ['label-danger', true],     // (3)
                    ['label-danger', true],     // (4)
                    ['label-danger', true],     // (5)
                    ['label-danger', true],     // (6)
                    ['label-info', false],      // (7)
                ]
            ]
        ];
    }

    /**
     * Test block_current_learning\helper::get_duedate_state by moving around the due date
     * to make sure the results of time period (1) through (7) are expected.
     *
     *          Warning Day      Alert Day      Due date
     *             |               |               |
     * Info ------>| Warning ----->| Danger ------>| Danger + Alert ---->
     * Time ------------------------------------------------------------>
     *      (7)   (6)     (5)     (4)     (3)     (2)      (1)
     *
     * @dataProvider data_get_duedate_state_returns_correct_states
     */
    public function test_get_duedate_state_returns_correct_states($alertperiod, $warningperiod, $expects) {
        $config = new stdClass();
        $config->alertperiod = $alertperiod;
        $config->warningperiod = $warningperiod;
        $now = time();

        array_splice($expects, 0, 0, '.'); // Make $expects 1-based indexes.

        // 1) the due date is in the past : danger + alert flag.
        $duedate = $now - DAYSECS;
        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertIsArray($result);
        $this->assertSame($expects[1][0], $result['state'], '1) result.state');
        $this->assertSame($expects[1][1], $result['alert'], '1) result.alert');

        // 2) the due date is right now : danger + alert flag.
        $duedate = $now;
        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertIsArray($result);
        $this->assertSame($expects[2][0], $result['state'], '2) result.state');
        $this->assertSame($expects[2][1], $result['alert'], '2) result.alert');

        // 3) (due date) - now < (alert period) : danger.
        $duedate = $now + $alertperiod / 2;
        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertIsArray($result);
        $this->assertSame($expects[3][0], $result['state'], '3) result.state');
        $this->assertSame($expects[3][1], $result['alert'], '3) result.alert');

        // 4) (due date) - now = (alert period) : danger.
        $duedate = $now + $alertperiod;
        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertIsArray($result);
        $this->assertSame($expects[4][0], $result['state'], '4) result.state');
        $this->assertSame($expects[4][1], $result['alert'], '4) result.alert');

        // 5) (alert period) < (due date) - now < (warning period) : warning.
        $duedate = $now + $alertperiod + ($warningperiod - $alertperiod) / 2;
        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertIsArray($result);
        $this->assertSame($expects[5][0], $result['state'], '5) result.state');
        $this->assertSame($expects[5][1], $result['alert'], '5) result.alert');

        // 6) (alert period) < (due date) - now = (warning period) : warning.
        $duedate = $now + $warningperiod;
        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertIsArray($result);
        $this->assertSame($expects[6][0], $result['state'], '6) result.state');
        $this->assertSame($expects[6][1], $result['alert'], '6) result.alert');

        // 7) (warning period) < (due date) - now: info.
        $duedate = $now + $alertperiod + $warningperiod + DAYSECS;
        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertIsArray($result);
        $this->assertSame($expects[7][0], $result['state'], '7) result.state');
        $this->assertSame($expects[7][1], $result['alert'], '7) result.alert');
    }
}
