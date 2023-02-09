<?php

use mod_perform\state\state_helper;

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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

abstract class state_testcase extends advanced_testcase {

    abstract protected static function get_object_type(): string;

    /**
     * Make sure no duplicates exist for state codes, internal names and translated names.
     * Also makes sure no lang strings were forgotten.
     */
    public function test_duplicates(): void {
        $this->assert_no_duplicates('get_code');
        $this->assert_no_duplicates('get_name');
        $this->assert_no_duplicates('get_display_name');
    }

    private function assert_no_duplicates(string $method_name) {
        $all_states = state_helper::get_all(static::get_object_type());
        $unique_results = $this->group_state_classes_by_type($all_states, $method_name);

        $count = 0;
        foreach ($unique_results as $states_per_type) {
            $count = $count + count($states_per_type);
        }
        $this->assertEquals(count($all_states), $count);
    }

    private function group_state_classes_by_type(array $all_states, string $method_name): array {
        $result = [];
        foreach ($all_states as $state_class) {
            $state_type = call_user_func([$state_class, 'get_type']);
            if (!array_key_exists($state_type, $result)) {
                $result[$state_type] = [];
            }

            $value = call_user_func([$state_class, $method_name]);
            if (!in_array($value, $result[$state_type], true)) {
                $result[$state_type][] = $value;
            }
        }

        return $result;
    }
}