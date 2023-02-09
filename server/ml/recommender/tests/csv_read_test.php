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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test CSV reader
 */
class ml_recommender_csv_read_testcase extends advanced_testcase {
    public $filepath = '';

    protected function setUp(): void {
        global $CFG;
        $this->filepath = $CFG->dataroot . '/ml_recommender_csv_reader_test.csv';
        file_put_contents($this->filepath, "id,name,mark\n1,\"User One\",7\n2,\"User's, Two\",\"8\"\n3,\"User Three\",9\n");
        parent::setUp();
    }

    protected function tearDown(): void {
        @unlink($this->filepath);
        $this->filepath = '';
        parent::tearDown();
    }

    /**
     * Test CSV reader as iterator
     */
    public function test_csv_read_iteration() {
        $reader = new \ml_recommender\local\csv\reader($this->filepath);
        // Run two times to confirm that rewind works properly.
        foreach ([1, 2] as $run) {
            $result = [];
            foreach ($reader as $read) {
                $result[] = $read;
            }
            $this->assertCount(3, $result, "Failed on run# $run");
            $this->assertEquals(['id' => 1, 'name' => 'User One', 'mark' => 7], $result[0], "Failed on run# $run");
            $this->assertEquals(['id' => 2, 'name' => 'User\'s, Two', 'mark' => 8], $result[1], "Failed on run# $run");
            $this->assertEquals(['id' => 3, 'name' => 'User Three', 'mark' => 9], $result[2], "Failed on run# $run");
        }
    }

    /**
     * Test CSV reader direct method calls
     */
    public function test_csv_read_calls() {
        $reader = new \ml_recommender\local\csv\reader($this->filepath);
        $this->assertTrue($reader->valid());
        $this->assertEquals(['id' => 1, 'name' => 'User One', 'mark' => 7], $reader->current());
        $this->assertEquals(1, $reader->key());

        $reader->next();
        $this->assertEquals(['id' => 2, 'name' => 'User\'s, Two', 'mark' => 8], $reader->current());
        $this->assertTrue($reader->valid());
        $this->assertEquals(['id' => 2, 'name' => 'User\'s, Two', 'mark' => 8], $reader->current());
        $this->assertEquals(2, $reader->key());

        $reader->rewind();
        $this->assertTrue($reader->valid());
        $this->assertEquals(['id' => 1, 'name' => 'User One', 'mark' => 7], $reader->current());
        $this->assertEquals(1, $reader->key());

        $reader->next();
        $reader->next();
        // No such record.
        $reader->next();
        $this->assertFalse($reader->valid());
        $this->assertEquals([], $reader->current());
        $this->assertEquals(0, $reader->key());

        // Recovery after closing
        $reader->rewind();
        $this->assertTrue($reader->valid());
        $this->assertEquals(['id' => 1, 'name' => 'User One', 'mark' => 7], $reader->current());
        $this->assertEquals(1, $reader->key());
    }
}