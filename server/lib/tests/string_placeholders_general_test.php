<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

use core\string_placeholders\general;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the general placeholders stuff that is replacing old $a instance
 */
class core_string_placeholders_general_testcase extends advanced_testcase {
    public function test_replace() {

        // Simple data.

        $string = 'Hello {$a}';
        $xa = 'Great "user"';
        $this->overrideLangString('edit', 'core', $string);

        $a = new general($xa);
        $result = $a->replace($string);
        $this->assertSame('Hello Great "user"', $result);

        $result2 = get_string('edit', 'core', $a);
        $this->assertSame($result, $result2);

        $result3 = get_string('edit', 'core', $xa);
        $this->assertSame($result, $result3);

        $a = new general($xa, true);
        $result = $a->replace($string);
        $this->assertSame('Hello Great &#34;user&#34;', $result);

        $result2 = get_string('edit', 'core', $a);
        $this->assertSame($result, $result2);

        // Complex data.

        $string = 'Hello {$a->name} {$a->guest} from {$a->user1->fullname} and {$a->user2->fullname}, {$a->missing}, {$a}, {$a->user->missing}, {$a->user1->missing}';
        $xa = [
            'name' => 'Pokus',
            'guest' => new lang_string('guest', 'core'),
        ];
        $this->overrideLangString('edit', 'core', $string);

        $a = new general($xa);
        $result = $a->replace($string);
        $this->assertSame('Hello Pokus Guest from {$a->user1->fullname} and {$a->user2->fullname}, {$a->missing}, {$a}, {$a->user->missing}, {$a->user1->missing}', $result);

        $result2 = get_string('edit', 'core', $a);
        $this->assertSame($result, $result2);

        $result3 = get_string('edit', 'core', $xa);
        $this->assertSame($result, $result3);

        $ya = new class($xa) {
            private $x;
            public function __construct($x) {
                $this->x = $x;
            }
            public function to_array() {
                return $this->x;
            }
        };
        $result2 = (new general($ya))->replace($string);
        $this->assertSame($result, $result2);
        $result2 = get_string('edit', 'core', $ya);
        $this->assertSame($result, $result2);

        $a = new general($xa, true);
        $result = $a->replace($string);
        $this->assertSame('Hello Pokus Guest from {$a->user1->fullname} and {$a->user2->fullname}, {$a->missing}, {$a}, {$a->user->missing}, {$a->user1->missing}', $result);

        $result2 = get_string('edit', 'core', $a);
        $this->assertSame($result, $result2);
    }
}