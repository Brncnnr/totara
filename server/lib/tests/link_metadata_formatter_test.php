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
use core\formatter\linkmetadata_formatter;
use core\format;

class core_link_metadata_formatter_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_normal_format(): void {
        $metadata_info = metadata_info::create_instance([
            'title' => 'This is the title',
            'description' => null,
            'url' => 'http://example.com?dota=2',
            'image' => 'http://example.com?doctor_me=22',
            'video:width' => '200',
            'video:height' => '150'
        ]);

        $formatter = new linkmetadata_formatter($metadata_info, context_system::instance());

        $this->assertSame("http://example.com?dota=2", $formatter->format('url'));
        $this->assertSame("http://example.com?doctor_me=22", $formatter->format('image'));
        $this->assertSame(200, $formatter->format('videowidth'));
        $this->assertSame(150, $formatter->format('videoheight'));
        $this->assertSame('This is the title', $formatter->format('title', format::FORMAT_PLAIN));
        $this->assertSame(null, $formatter->format('description', format::FORMAT_PLAIN));
    }

    /**
     * @return void
     */
    public function test_format_invalid_data(): void {
        $metadata_info = new metadata_info();

        // Set all the private property inside the metadata info so that
        // we can check if the formatter is actually santizing on the way out.
        $ref_class = new ReflectionClass($metadata_info);

        $title_property = $ref_class->getProperty('title');
        $title_property->setAccessible(true);
        $title_property->setValue($metadata_info, "<script>alert('hello world');</script>");

        $description_property = $ref_class->getProperty('description');
        $description_property->setAccessible(true);
        $description_property->setValue($metadata_info, "<img href='xxme' onerror='alert(\"hello stranger\");'>");

        $url_property = $ref_class->getProperty("url");
        $url_property->setAccessible(true);
        $url_property->setValue($metadata_info, "file://some-valid-file-path?dd=15");

        $image_property = $ref_class->getProperty('image');
        $image_property->setAccessible(true);
        $image_property->setValue($metadata_info, "http://example.com");

        $formatter = new linkmetadata_formatter($metadata_info, context_system::instance());

        $this->assertSame(
            'alert(\'hello world\');',
            $formatter->format('title', format::FORMAT_PLAIN)
        );

        $this->assertEmpty($formatter->format('description', format::FORMAT_PLAIN));

        $this->assertNull($formatter->format('url'));
        $this->assertSame('http://example.com', $formatter->format('image'));

        $this->assertNull($formatter->format('videowidth'));
        $this->assertNull($formatter->format('videoheight'));
    }
}