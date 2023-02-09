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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @group orm
 */

use core\pagination\cursor;
use core\pagination\cursor_paginator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

class core_orm_cursor_testcase extends basic_testcase {

    public function test_using_invalid_cursor_not_base64() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid cursor given, expected base64 encoded string');

        $cursor = '-----';
        cursor::decode($cursor);
    }

    public function test_using_invalid_cursor_not_json_encoded() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid cursor given, expected array encoded as json and base64');

        $cursor = base64_encode('foobar');
        cursor::decode($cursor);
    }

    public function test_using_invalid_cursor_empty_array() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Empty cursor given, please provide a cursor with at least one value');

        $cursor = base64_encode(json_encode([]));
        cursor::decode($cursor);
    }

    public function test_using_invalid_cursor_not_array() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid cursor given, expected array encoded as json and base64');

        $cursor = base64_encode(json_encode('foo'));
        cursor::decode($cursor);
    }

    public function test_missing_limit() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You must provide a limit within your cursor.');

        new cursor([
            'columns' => []
        ]);
    }

    public function test_missing_columns() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You must provide columns within your cursor.');

        new cursor([
            'limit' => null
        ]);
    }

    public function test_invalid_column_keys() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expecting an array with column names as keys');
        $cursor = new cursor();
        $cursor->set_columns([
            0, 'foo',
            'col1' => 'bar'
        ]);
    }

    public function test_valid_cursor() {
        $expected_cursor = [
            'limit' => cursor_paginator::DEFAULT_ITEMS_PER_PAGE,
            'columns' => null
        ];

        $cursor = new cursor();
        $this->assertEquals($expected_cursor, $cursor->get_cursor());
        $this->assertEquals(cursor_paginator::DEFAULT_ITEMS_PER_PAGE, $cursor->get_limit());
        $this->assertEquals(null, $cursor->get_columns());

        // Test the setter
        $cursor->set_limit(10);

        $this->assertEquals(10, $cursor->get_limit());

        $this->assertEquals([
            'limit' => 10,
            'columns' => null
        ], $cursor->get_cursor());

        $this->assertEquals(null, $cursor->get_columns());

        // Test the setter
        $cursor->set_columns([
            'name' => 'Jane',
            'id' => '10'
        ]);

        $this->assertEquals([
            'name' => 'Jane',
            'id' => '10'
        ], $cursor->get_columns());

        $this->assertEquals([
            'limit' => 10,
            'columns' => [
                'name' => 'Jane',
                'id' => '10'
            ]
        ], $cursor->get_cursor());

        $expected_cursor = [
            'limit' => 0,
            'columns' => null
        ];

        $cursor = new cursor($expected_cursor);
        $this->assertEquals($expected_cursor, $cursor->get_cursor());
        $this->assertEquals(0, $cursor->get_limit());
        $this->assertEquals(null, $cursor->get_columns());

        $expected_cursor = [
            'limit' => 0,
            'columns' => []
        ];

        $cursor = new cursor($expected_cursor);
        $this->assertEquals($expected_cursor, $cursor->get_cursor());
        $this->assertEquals(0, $cursor->get_limit());
        $this->assertEquals([], $cursor->get_columns());

        $expected_cursor = [
            'limit' => 10,
            'columns' => [
                'name' => 'John',
                'id' => '1'
            ]
        ];

        $encoded_cursor = "eyJsaW1pdCI6MTAsImNvbHVtbnMiOnsibmFtZSI6IkpvaG4iLCJpZCI6IjEifX0=";

        $cursor = new cursor($expected_cursor);
        $this->assertEquals($encoded_cursor, $cursor->encode());
        $this->assertEquals($expected_cursor, $cursor->get_cursor());
        $this->assertEquals(10, $cursor->get_limit());
        $this->assertEquals(
            [
                'name' => 'John',
                'id' => '1'
            ],
            $cursor->get_columns()
        );

        $cursor = cursor::decode($encoded_cursor);
        $this->assertEquals($encoded_cursor, $cursor->encode());
        $this->assertEquals($expected_cursor, $cursor->get_cursor());
        $this->assertEquals(10, $cursor->get_limit());
        $this->assertEquals(
            [
                'name' => 'John',
                'id' => '1'
            ],
            $cursor->get_columns()
        );
    }

}
