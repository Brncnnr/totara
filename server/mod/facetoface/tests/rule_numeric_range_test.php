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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot .'/mod/facetoface/mod_form.php');

/**
 * mod_facetoface_dashboard_filter_testcase class
 */
class mod_facetoface_rule_numeric_range_testcase extends advanced_testcase {

    public function test_mod_facetoface_rule_numeric_range() {
        $floatrange = ['min' => 0, 'max' => 42.5];
        $intrange = ['min' => -1, 'max' => 9999, 'int' => true];
        $intonly = ['int' => true];
        $nulloptions = null;

        // Given $f = PHP_INT_MAX, (float)$f != (int)$f -- even though (float)PHP_INT_MAX == (int)PHP_INT_MAX
        $realmaxint = PHP_INT_MAX - 512;

        //   test,       valid,  options
        $tests = [
            ['apple',    false,  $floatrange],
            ['-2',       false,  $floatrange],
            [0,          true,   $floatrange],
            [1.5,        true,   $floatrange],
            [' 1.5 ',    true,   $floatrange],
            [42.5,       true,   $floatrange],
            ['42.5',     true,   $floatrange],
            ['42.5000',  true,   $floatrange],
            ['42.50001', false,  $floatrange],
            ['42.6',     false,  $floatrange],
            ['99',       false,  $floatrange],
            ['-2',       false,  $intrange],
            ['-1',       true,   $intrange],
            [-1,         true,   $intrange],
            ['-1.0000',  true,   $intrange],
            [-1.0000,    true,   $intrange],
            [0,          true,   $intrange],
            [1.5,        false,  $intrange],
            ['9999',     true,   $intrange],
            ['9 999',    true,   $intrange],
            [' 9 999  ', true,   $intrange],
            ['10000',    false,  $intrange],
            ['apple',    false,  $intonly],
            [-1,         true,   $intonly],
            [0,          true,   $intonly],
            [0.56,       false,  $intonly],
            ['99.99',    false,  $intonly],
            ['10000',    true,   $intonly],
            [$realmaxint,       true,   $intonly],
            [PHP_INT_MAX,       false,   $intonly],
            [PHP_INT_MIN,       true,    $intonly],
            [PHP_FLOAT_MAX,     false,   $intonly],
            [PHP_FLOAT_MIN,     false,   $intonly],
            [PHP_FLOAT_EPSILON, false,   $intonly],
            ['apple',    false,  $nulloptions],
            [-1,         true,   $nulloptions],
            [0,          true,   $nulloptions],
            [0.56,       true,   $nulloptions],
            ['99.99',    true,   $nulloptions],
            ['10000',    true,   $nulloptions],
            [PHP_INT_MAX,       true,   $nulloptions],
            [PHP_INT_MIN,       true,   $nulloptions],
            [PHP_FLOAT_MAX,     true,   $nulloptions],
            [PHP_FLOAT_MIN,     true,   $nulloptions],
            [PHP_FLOAT_EPSILON, true,   $nulloptions],
        ];

        $rule = new mod_facetoface_rule_numeric_range();
        foreach ($tests as $px => $plan) {
            $index = $px + 1;
            $this->assertEquals($plan[1], $rule->validate($plan[0], $plan[2]), "When validating #{$index} {$plan[0]} with ".print_r($plan[2], 1));
        }
    }
}
