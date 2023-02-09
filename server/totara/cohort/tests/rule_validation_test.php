<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/date.php'); // constants.


use totara_cohort\rules\ui\text as ui_text;
use totara_cohort\rules\ui\menu as ui_menu;
use totara_cohort\rules\ui\multiselect as ui_multiselect;
use totara_cohort\rules\ui\checkbox as ui_checkbox;
use totara_cohort\rules\ui\date as ui_date;
use totara_cohort\rules\ui\date_no_timezone as ui_datenotz;
use totara_cohort\rules\ui\none_min_max_exactly as ui_noneminmaxexactly;

use totara_cohort\rules\ui\form_empty as base_form;
use totara_cohort\rules\ui\form_date as date_form;

class totara_cohort_rule_validation_testcase extends advanced_testcase {

    /**
     * Data provider for test_text_rule_validation.
     *
     * @return []
     */
    public function data_text_rules() {
        $data = [
            [null, null, false], // Empty 'equal' value failure.
            [9876, 'zx', false], // Out of scope 'equal' value failure.
            [1, null, false],    // Empty 'lov' value failure.
            [5, null, true],     // Empty 'lov' value, with correct 'equal' value.
            [1, 'zx', true],     // Correct values for both.
            [1, '0', true],      // Double check '0' can be used for 'lov'.
        ];
        return $data;
    }

    /**
     * @dataProvider data_text_rules
     */
    public function test_text_rule_validation($equal, $lov, $result) {
        $rule = new ui_text(
            'rulename',
            'rule description'
        );
        base_form::mock_submit(['equal' => $equal, 'listofvalues' => $lov]);
        $this->assertEquals($result, $rule->validateResponse());
    }

    /**
     * Data provider for test_noneminmaxexactly_rule_validation.
     *
     * @return []
     */
    public function data_noneminmaxexactly_rules() {
        $data = [
            [null, null, false], // null operator
            [9876, null, false], // invalid operator
            [ui_noneminmaxexactly::COHORT_RULES_OP_MIN, null, false], // valid operator null lov
            [ui_noneminmaxexactly::COHORT_RULES_OP_NONE, null, true], // valid operastor, valid null lov
            [ui_noneminmaxexactly::COHORT_RULES_OP_MIN, 'q', false],  // valid operator, invalid lov
            [ui_noneminmaxexactly::COHORT_RULES_OP_MIN, 0, false],    // valid operator, invalid lov
            [ui_noneminmaxexactly::COHORT_RULES_OP_MIN, 1, true]      // valid operator, valid lov
        ];
        return $data;
    }

    /**
     * @dataProvider data_noneminmaxexactly_rules
     */
    public function test_noneminmaxexactly_rule_validation($equal, $lov, $result) {
        $rule = new ui_noneminmaxexactly(
            'rulename',
            'rule description'
        );
        base_form::mock_submit(['equal' => $equal, 'listofvalues' => $lov]);
        $this->assertEquals($result, $rule->validateResponse());
    }

    /**
     * Data provider for test_datenotimezone_rule_validation.
     *
     * @return []
     */
    public function data_datenotimezone_rules() {
        $data = [
            [null, [], false], // Empty fixedordynamic
            [9876, [], false], // Invalid fixedordynamic
            [
                ui_datenotz::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => null,
                 'beforeafterdate' => '1/1/2020'
                ],
                false
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'beforeafterdate' => '1/1/2020'
                ],
                false
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'beforeafterdate' => ''
                ],
                false
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'beforeafterdate' => '1/1/2020'
                ],
                true
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_DYNAMIC,
                [
                    'durationmenu' => null,
                    'durationdate' => '3'
                ],
                false
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'durationdate' => '3'
                ],
                false
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'durationdate' => null
                ],
                false
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'durationdate' => 'three'
                ],
                false
            ],
            [
                ui_datenotz::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'durationdate' => '3'
                ],
                true
            ], // Dynamic rule with fixed operator
        ];
        return $data;
    }

    /**
     * @dataProvider data_datenotimezone_rules
     */
    public function test_datenotimezone_rule_validation($dynamic, $data, $result) {
        $rule = new ui_datenotz(
            'rulename',
            'rule description'
        );
        $data['fixedordynamic'] = $dynamic;
        date_form::mock_submit($data);
        $this->assertEquals($result, $rule->validateResponse());
    }

    /**
     * Data provider for test_date_rule_validation.
     */
    public function data_date_rules() {
        $data = [
            [null, [], false], // Empty fixedordynamic
            [9876, [], false], // Invalid fixedordynamic
            [
                ui_date::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => null,
                 'beforeafterdatetime_raw' => '2019-04-30 13:45:00',
                 'beforeafterdatetime_timezone' => 'Pacific/Auckland',
                 'beforeafterdatetime' => '1556588700'
                ],
                false
            ],
            [
                ui_date::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'beforeafterdatetime_raw' => '2019-04-30 13:45:00',
                 'beforeafterdatetime_timezone' => 'Pacific/Auckland',
                 'beforeafterdatetime' => '1556588700'
                ],
                false
            ],
            [
                ui_date::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'beforeafterdatetime_raw' => '',
                 'beforeafterdatetime_timezone' => 'Pacific/Auckland',
                 'beforeafterdatetime' => '1556588700'
                ],
                true // This gets auto filled on form submission so can never fail.
            ],
            [
                ui_date::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'beforeafterdatetime_raw' => '2019-04-30 13:45:00',
                 'beforeafterdatetime_timezone' => '',
                 'beforeafterdatetime' => '1556588700'
                ],
                true // This gets auto filled on form submission so can never fail.
            ],
            [
                ui_date::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'beforeafterdatetime_raw' => '2019-04-30 13:45:00',
                 'beforeafterdatetime_timezone' => 'Pacific/Auckland',
                 'beforeafterdate' => ''
                ],
                true // This gets auto filled on form submission so can never fail.
            ],
            [
                ui_date::COHORT_RULE_DATE_FIXED,
                [
                 'beforeaftermenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'beforeafterdatetime_raw' => '2019-04-30 13:45:00',
                 'beforeafterdatetime_timezone' => 'Pacific/Auckland',
                 'beforeafterdatetime' => 1556588700
                ],
                true
            ],
            [
                ui_date::COHORT_RULE_DATE_DYNAMIC,
                [
                    'durationmenu' => null,
                    'durationdate' => '3'
                ],
                false
            ],
            [
                ui_date::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE,
                 'durationdate' => '3'
                ],
                false
            ],
            [
                ui_date::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'durationdate' => null
                ],
                false
            ],
            [
                ui_date::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'durationdate' => 'three'
                ],
                false
            ],
            [
                ui_date::COHORT_RULE_DATE_DYNAMIC,
                [
                 'durationmenu' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                 'durationdate' => '3'
                ],
                true
            ],
        ];
        return $data;
    }

    /**
     * @dataProvider data_date_rules
     */
    public function test_date_rule_validation($dynamic, $data, $result) {
        $rule = new ui_date(
            'rulename',
            'rulename description'
        );
        $data['fixedordynamic'] = $dynamic;
        date_form::mock_submit($data);
        $this->assertEquals($result, $rule->validateResponse());
    }

    /**
     * Data provider for test_checkbox_rule_validation.
     */
    public function data_checkbox_rules() {
        $data = [
            [null, false],
            [0, true],
            [1, true],
            [2, false]
        ];
        return $data;
    }

    /**
     * @dataProvider data_checkbox_rules
     */
    public function test_checkbox_rule_validation($lov, $result) {
        $rule = new ui_checkbox(
            'rulename',
            [
                0 => 'no',
                1 => 'yes'
            ]

        );
        base_form::mock_submit(['listofvalues' => $lov]);
        $this->assertEquals($result, $rule->validateResponse());
    }

    /**
     * Data provider for test_menu_rule_validation.
     */
    public function data_menu_rules() {
        $data = [
            [null, null, false],
            [9999, null, false],
            [0, null, false],
            [0, [], false],
            [0, [1,2], true], // Note: form submission removes the 1.
            [0, [2,4], true],
            [1, [2,4], true],
            [1, [1,2], true], // Note: form submission removes the 1.
            [2, [2,4], false]
        ];
        return $data;
    }

    /**
     * @dataProvider data_menu_rules
     */
    public function test_menu_rule_validation($equal, $lov, $result) {
        $rule = new ui_menu(
            'rulename',
            [
                2 => 'option1',
                4 => 'option2',
                6 => 'option3'
            ]
        );
        base_form::mock_submit(['equal' => $equal, 'listofvalues' => $lov]);
        $this->assertEquals($result, $rule->validateResponse());
    }

    /**
     * Data provider for test_text_rule_validation.
     *
     * equal(is 2, isnot 6), exact(any 0, all 1), [hash=>1,hash=>0], result
     */
    public function data_multiselect_rules() {
        $data = [
            [null, 1, ['hash1' => 1], false],
            [9876, 1, ['hash1' => 1], false],
            [2, null, ['hash1' => 1], false],
            [2, 9876, ['hash1' => 1], false],
            [6, 0, [], false],
            [6, 0, ['hash4' => 1], false],
            [6, 0, ['hash3' => 1], true],
            [
                6,
                0,
                [
                    'hash1' => 1,
                    'hash2' => 1,
                    'hash3' => 1
                ],
                true
            ],
            [
                6,
                0,
                [
                    'hash1' => 0,
                    'hash2' => 0,
                    'hash3' => 0
                ],
                false
            ],
        ];
        return $data;
    }

    /**
     * @dataProvider data_multiselect_rules
     */
    public function test_multiselect_rule_validation($equal, $exact, $lov, $result) {
        $rule = new ui_multiselect(
            'rulename',
            'rulename description',
            [
                'hash1' => ['option' => 'value1', 'icon' => ''],
                'hash2' => ['option' => 'value2', 'icon' => ''],
                'hash3' => ['option' => 'value3', 'icon' => '']
            ]
        );
        base_form::mock_submit(['equal' => $equal, 'exact' => $exact, 'options' => $lov]);
        $this->assertEquals($result, $rule->validateResponse());
    }
}
