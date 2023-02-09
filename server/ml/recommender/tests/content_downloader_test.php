<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;
use ml_recommender\local\environment;
use ml_recommender\local\export\content_downloader;
use ml_recommender\local\flag;
use ml_service\auth\token_manager;

class ml_recommender_content_downloader_testcase extends testcase {
    /**
     * @var string|null
     */
    private $file_path;

    /**
     * Verify that only valid requests can access the files
     */
    public function test_is_request_allowed(): void {
        global $CFG;
        $CFG->ml_service_key = 'abc';

        // Bad file area
        $downloader = content_downloader::make('a', ['b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Arg count incorrect
        $downloader = content_downloader::make('export', ['abc', 'b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Request time invalid/missing
        $_SERVER['HTTP_X_TOTARA_TIME'] = null;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = null;
        $downloader = content_downloader::make('export', ['b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Time missing, token provided
        $_SERVER['HTTP_X_TOTARA_TIME'] = null;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = 'abc123';
        $downloader = content_downloader::make('export', ['b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Token missing, time provided
        $_SERVER['HTTP_X_TOTARA_TIME'] = 123456789;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = null;
        $downloader = content_downloader::make('export', ['b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Assert a valid token is acceptable
        // To prevent random time errors, we're going to force the relative time
        // to our own value.
        $base = new ReflectionProperty(token_manager::class, 'base_time');
        $base->setAccessible(true);
        $base->setValue(123456);

        // Time too old
        $_SERVER['HTTP_X_TOTARA_TIME'] = 1000;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = 'abc123';
        $downloader = content_downloader::make('export', ['b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Time too new
        $_SERVER['HTTP_X_TOTARA_TIME'] = 123456789;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = 'abc123';
        $downloader = content_downloader::make('export', ['b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Time is good but token doesn't match
        $_SERVER['HTTP_X_TOTARA_TIME'] = 123457;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = 'abc123';
        $downloader = content_downloader::make('export', ['b.csv'], '/a/b/');
        self::assertFalse($downloader->is_request_allowed());

        // Time is good, token is good
        $_SERVER['HTTP_X_TOTARA_TIME'] = 123457;
        $_SERVER['HTTP_X_TOTARA_ML_KEY'] = token_manager::make_token(123457);
        $downloader = content_downloader::make('export', ['b.csv'], '/a/b/');
        self::assertTrue($downloader->is_request_allowed());

        // Reset back
        $base->setValue(null);
    }

    /**
     * Verify that we can only find files we know about
     */
    public function test_find_matching_file(): void {
        global $CFG;

        // Bad request
        $downloader = content_downloader::make('a', [null], $this->file_path);
        self::assertNull($downloader->find_matching_file());

        // Not a known file
        $downloader = content_downloader::make('export', ['abc.csv'], $this->file_path);
        self::assertNull($downloader->find_matching_file());

        // Not a file in the known list (don't let downloads to other files happen)
        $downloader = content_downloader::make('export', ['index.php'], $CFG->dirroot);
        self::assertNull($downloader->find_matching_file());

        // Create the real file
        file_put_contents($this->file_path . 'tenants.csv', 'a,b,c');
        $downloader = content_downloader::make('export', ['tenants.csv'], $this->file_path);
        $found_file = $downloader->find_matching_file();
        self::assertEquals($this->file_path . 'tenants.csv', $found_file);
    }

    /**
     * Test the is_file_available function
     */
    public function test_is_file_available(): void {
        $interactions_file = $this->file_path . 'user_interactions_0.csv';

        // Status not complete
        $downloader = content_downloader::make('export', ['user_interactions_0.csv'], $this->file_path);
        self::assertFalse($downloader->is_file_available($interactions_file));

        // Status complete, but no real file
        flag::start(flag::EXPORT, $this->file_path);
        flag::complete(flag::EXPORT, $this->file_path);
        self::assertFalse($downloader->is_file_available($interactions_file));

        // Real file
        file_put_contents($interactions_file, 'a,b,c');
        $downloader = content_downloader::make('export', ['user_interactions_0.csv'], $this->file_path);
        self::assertTrue($downloader->is_file_available($interactions_file));
    }

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
            require_once("$CFG->dirroot/lib/filelib.php");
            fulldelete($this->file_path);
        }

        $this->file_path = null;
    }
}