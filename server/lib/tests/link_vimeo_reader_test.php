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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */

use core_phpunit\testcase;
use core\link\reader_factory;
use core\link\vimeo_reader;
use core\link\metadata_info;
use core\link\metadata_reader;

defined('MOODLE_INTERNAL') || die();

class core_link_vimeo_reader_testcase extends advanced_testcase {

    public function test_vimeo_reader(): void {
        global $CFG;
        $vimeo_url = 'https://vimeo.com/1';

        \mock_vimeo_request::add_mock_url($vimeo_url);

        $reader = reader_factory::get_reader_classname($vimeo_url);
        self::assertEquals(vimeo_reader::class, $reader);

        /** @var metadata_info $meta_data */
        $meta_data = $reader::get_metadata_info($vimeo_url);

        self::assertEquals('Test title', $meta_data->get_title());
        self::assertEquals('Test description', $meta_data->get_description());
        self::assertEquals($vimeo_url, $meta_data->get_url());
        self::assertEquals(100, $meta_data->get_video_width());
        self::assertEquals(100, $meta_data->get_video_height());
        self::assertEquals('test.jpg', $meta_data->get_image());
    }

    public function test_vimeo_reader_with_incorrect_url(): void {
        $mock_url = 'https://example.com';

        $reader = reader_factory::get_reader_classname($mock_url);
        self::assertNotEquals(vimeo_reader::class, $reader);
        self::assertEquals(metadata_reader::class, $reader);
    }

    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/tests/fixtures/link/mock_vimeo_request.php");
        // Clear core_link mock url
        \mock_vimeo_request::clear();
    }

    protected function tearDown(): void {
        // Clear core_link mock url
        \mock_vimeo_request::clear();
    }
}