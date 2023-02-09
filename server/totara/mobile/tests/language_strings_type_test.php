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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

use totara_webapi\phpunit\webapi_phpunit_helper;

defined('MOODLE_INTERNAL') || die();

class totara_mobile_language_strings_type_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $language_string, array $args = []) {
        return $this->resolve_graphql_type('totara_mobile_language_string', $field, $language_string, $args);
    }

    /**
     * Test type resolver
     */
    public function test_resolve_language_strings() {
        $this->assertEquals('b', $this->resolve('json_string', ['json_string' => 'b']));
        $this->assertNull($this->resolve('id', ['json_string' => 'b']));
        $this->assertNull($this->resolve('json_string', ['another_string' => 'b']));
        $this->assertEquals('b', $this->resolve('json_string', ['level' => 'a', 'json_string' => 'b', 'type' => 'c']));
    }
}