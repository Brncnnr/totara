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
 * Test CSV writer
 */
class ml_recommender_csv_write_testcase extends advanced_testcase {
    public $filepath = '';

    protected function setUp(): void {
        global $CFG;
        $this->filepath = $CFG->dataroot . '/ml_recommender_csv_writer_test.csv';
        parent::setUp();
    }

    protected function tearDown(): void {
        @unlink($this->filepath);
        $this->filepath = '';
        parent::tearDown();
    }

    public function test_csv_write() {
        $writer = new \ml_recommender\local\csv\writer($this->filepath);
        $writer->add_headings(['id', 'name', 'mark']);
        $writer->add_data([1, 'User One', "7"]);
        $writer->add_data([2, 'User\'s, Two', "8"]);
        $writer->close();
        $loaded = file($this->filepath);
        $this->assertCount(3, $loaded);
        $this->assertEquals("id,name,mark\n", $loaded[0]);
        $this->assertEquals("1,\"User One\",7\n", $loaded[1]);
        $this->assertEquals("2,\"User's, Two\",8\n", $loaded[2]);
    }

    /**
     * Test prevention of writing headers after data
     */
    public function test_csv_headers_sequence() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Could not write headers to CSV since rows were already added');

        $writer = new \ml_recommender\local\csv\writer($this->filepath);
        $writer->add_data([1, 'User One', "7"]);
        $writer->add_headings(['id', 'name', 'mark']);
    }
}