<?php
/*
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package core_dml
 */
defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;

class core_dml_array_recordset_testcase extends testcase {

    /**
     * Assert that array_recordset can handle a mix of array and object records.
     *
     * @return void
     */
    public function test_recordset_objects(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/dml/array_recordset.php');

        $data = [
            1 => ['a' => 1, 'b' => 2, 'c' => 3],
            4 => (object)['a' => 4, 'b' => 5, 'c' => 6],
        ];
        $recordset = new array_recordset($data);

        $first = $recordset->current();
        self::assertIsObject($first);
        self::assertEqualsCanonicalizing($data[1]['a'], $first->a);
        self::assertEquals(1, $recordset->key());

        $recordset->next();
        $second = $recordset->current();
        self::assertIsObject($second);
        self::assertEqualsCanonicalizing($data[4]->a, $second->a);
        self::assertEquals(4, $recordset->key());
    }

}