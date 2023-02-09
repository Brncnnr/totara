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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package engage_article
 */

defined('MOODLE_INTERNAL') || die();

use core\json_editor\document;
use engage_article\local\image_processor;

class engage_article_image_processor_testcase extends advanced_testcase {
    /**
     * Validate the following:
     *   1. Correct node is found when presented with multiples
     */
    public function test_find_first_valid_node() {
        $content = $this->get_test_cases();
        $processor = image_processor::make(0, 0);

        // Image
        $document = document::create($content['case1']);
        $first_node = $processor->find_first_valid_image_node($document);
        $this->assertInstanceOf('core\json_editor\node\image', $first_node);
        self::assertEquals('test alt', $first_node->get_alt_text());

        // Link Media (Image)
        $document = document::create($content['case2']);
        $first_node = $processor->find_first_valid_image_node($document);
        $this->assertInstanceOf('core\json_editor\node\link_media', $first_node);
        $info = $first_node->get_info();
        $this->assertEquals('https://example.com/second-image.png', $info['image']);

        // Link Media (Video)
        $document = document::create($content['case3']);
        $first_node = $processor->find_first_valid_image_node($document);
        $this->assertInstanceOf('core\json_editor\node\link_media', $first_node);
        $info = $first_node->get_info();
        $this->assertEquals('https://example.com/sample-image.jpg', $info['image']);

        // No valid image (bad video)
        $document = document::create($content['case4']);
        $first_node = $processor->find_first_valid_image_node($document);
        $this->assertNull($first_node);

        // No valid image (no nodes)
        $document = document::create($content['case5']);
        $first_node = $processor->find_first_valid_image_node($document);
        $this->assertNull($first_node);
    }

    /**
     * @return array
     */
    private function get_test_cases() {
        $raw_content = file_get_contents(__DIR__ . '/fixtures/image_parser_testcases.json');
        return json_decode($raw_content, true);
    }
}