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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\helper\node_helper;

class core_json_editor_node_helper_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_check_keys_match(): void {
        $this->assertTrue(
            node_helper::check_keys_match(['data', 'x', 'me'], ['data', 'x', 'me'])
        );

        $this->assertTrue(
            node_helper::check_keys_match(['data', 'x', 'me'], ['data', 'x'], ['me'])
        );

        $this->assertTrue(
            node_helper::check_keys_match(['data', 'x', 'me'], ['x'], ['data', 'me'])
        );

        // Just silence the debugging when they are not match.
        set_config('debugdeveloper', false);

        $this->assertFalse(
            node_helper::check_keys_match(['data', 'd', 'e'], ['data', 'd'])
        );

        $this->assertFalse(
            node_helper::check_keys_match([], ['de', 'd', 'z'])
        );

        $this->assertFalse(
            node_helper::check_keys_match(['de','d', 'z', 'cd'], ['de', 'd', 'z'], ['cc'])
        );
    }

    /**
     * @return void
     */
    public function test_checks_keys_match_for_data(): void {
        $data = [
            'd' => 15,
            'x' => 12,
            'cc' => null
        ];

        $this->assertTrue(node_helper::check_keys_match_against_data($data, ['d', 'x'], ['cc']));

        // Just silence the debugging when they are not match.
        set_config('debugdeveloper', false);
        $this->assertFalse(node_helper::check_keys_match_against_data($data, ['d', 'cc']));
        $this->assertFalse(node_helper::check_keys_match_against_data($data, ['d']));
    }
}
