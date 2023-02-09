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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\link\metadata_info;

class core_url_metadata_info_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_clean_invalid_inputs(): void {
        $metadata = metadata_info::create_instance([
            'title' => "<script type='text/javascript'>alert('hello -world');</script>",
            'description' => "<img src='Something dumb' onerror=\"alert('hi')\"/>",
            'url' => 'mailto://admin@example.com',
            'image' => 'http://example.com',
            'video:height' => 'hello world',
            'video:width' => 'hello ddd'
        ]);

        $this->assertSame('alert(\'hello -world\');', $metadata->get_title());
        $this->assertEmpty($metadata->get_description());
        $this->assertNull($metadata->get_url());
        $this->assertInstanceOf(\moodle_url::class, $metadata->get_image());
        $this->assertEquals("http://example.com", $metadata->get_image()->out());

        $this->assertEquals(0, $metadata->get_video_width());
        $this->assertEquals(0, $metadata->get_video_height());
    }

    /**
     * @return void
     */
    public function test_clean_valid_inputs(): void {
        $metadata = metadata_info::create_instance([
            'title' => 'This is the title',
            'description' => 'This is the description',
            'url' => "http://example.com?x=5",
            'image' => "http://example.com?dd=55",
            "video:height" => "150",
            "video:width" => "200"
        ]);

        $this->assertSame("This is the title", $metadata->get_title());
        $this->assertSame("This is the description", $metadata->get_description());

        $this->assertSame("http://example.com?x=5", $metadata->get_url()->out());
        $this->assertSame("http://example.com?dd=55", $metadata->get_image()->out());
        $this->assertSame(150, $metadata->get_video_height());
        $this->assertSame(200, $metadata->get_video_width());
    }
}