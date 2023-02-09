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
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use ml_recommender\local\environment;
use ml_recommender\local\flag;

class ml_recommender_flag_testcase extends advanced_testcase {
    /**
     * @var string|null
     */
    private $file_path;

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->file_path = environment::get_data_path();
        // Create the directory path.
        if (!is_dir($this->file_path)) {
            make_writable_directory($this->file_path);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        global $CFG;

        if (file_exists($this->file_path)) {
            require_once("{$CFG->dirroot}/lib/filelib.php");
            fulldelete($this->file_path);
        }

        $this->file_path = null;
    }

    /**
     * @return void
     */
    public function test_start_export(): void {
        self::assertTrue(flag::start(flag::EXPORT, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_export_in_progress(): void {
        self::assertTrue(flag::start(flag::EXPORT, $this->file_path));
        self::assertTrue(flag::in_progress(flag::EXPORT, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_export_complete(): void {
        self::assertTrue(flag::complete(flag::EXPORT, $this->file_path));
        self::assertFalse(flag::in_progress(flag::EXPORT, $this->file_path));
        self::assertTrue(flag::is_complete(flag::EXPORT, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_start_import(): void {
        self::assertTrue(flag::start(flag::IMPORT, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_import_in_progress(): void {
        self::assertTrue(flag::start(flag::IMPORT, $this->file_path));
        self::assertTrue(flag::in_progress(flag::IMPORT, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_import_complete(): void {
        self::assertTrue(flag::complete(flag::IMPORT, $this->file_path));
        self::assertFalse(flag::in_progress(flag::IMPORT, $this->file_path));
        self::assertTrue(flag::is_complete(flag::IMPORT, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_start_ml(): void {
        self::assertTrue(flag::start(flag::ML, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_ml_in_progress(): void {
        self::assertTrue(flag::start(flag::ML, $this->file_path));
        self::assertTrue(flag::in_progress(flag::ML, $this->file_path));
    }

    /**
     * @return void
     */
    public function test_ml_complete(): void {
        self::assertTrue(flag::complete(flag::ML, $this->file_path));
        self::assertFalse(flag::in_progress(flag::ML, $this->file_path));
        self::assertTrue(flag::is_complete(flag::ML, $this->file_path));
    }
}