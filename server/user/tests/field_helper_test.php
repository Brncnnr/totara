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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */
defined('MOODLE_INTERNAL') || die();

use core_user\profile\field\field_helper;

/**
 * Unit test for class {@see field_helper}
 */
class core_user_field_helper_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_format_position_key(): void {
        for ($i = 0; $i < 5; $i++) {
            $value = field_helper::format_position_key($i);
            $this->assertSame("position_{$i}", $value);
        }
    }

    /**
     * This is to make sure that if anyone changes the behaviour of the function will
     * have to fix this test as well.
     *
     * @return void
     */
    public function test_format_custom_field(): void {
        $custom_fields = [
            'ccc_dd',
            'dd_cc',
            'add_gfgf',
            'ckiokoko_lpdlwpq'
        ];

        foreach ($custom_fields as $custom_field) {
            $value = field_helper::format_custom_field_short_name($custom_field);
            $this->assertSame("profile_field_{$custom_field}", $value);
        }
    }
}