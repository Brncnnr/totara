<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package performelement_static_element
 */

use core\format;
use mod_perform\formatter\activity\element_data_field_formatter;
use mod_perform\models\activity\element_plugin;
use performelement_static_content\formatter\data_field_formatter;
use performelement_static_content\static_content;
use core\json_editor\node\paragraph;

/**
 * @group perform
 * @group perform_element
 */
class performelement_static_content_data_field_formatter_testcase extends advanced_testcase {

    public function test_format() {
        global $CFG;
        require_once($CFG->libdir . '/filterlib.php');

        /** @var static_content $plugin */
        $plugin = element_plugin::load_by_plugin('static_content');

        // Initiate through main class
        $formatter_string = element_data_field_formatter::for_plugin($plugin);
        $this->assertEquals(data_field_formatter::class, $formatter_string);

        $context = context_system::instance();
        $format = format::FORMAT_PLAIN;

        $formatter = new data_field_formatter($format, $context);

        $data['wekaDoc'] = $this->get_weka_document();
        $data['docFormat'] = 'FORMAT_JSON_EDITOR';
        $data['format'] = 'HTML';
        $data['element_id'] = 1;
        $data = json_encode($data);

        $result = $formatter->format($data);
        $result = json_decode($result, true);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('<div class="tui-rendered"><p>This is a test</p></div>', $result['content']);
    }

    private function get_weka_document(): string {
        return json_encode(
            [
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('This is a test')]
            ]
        );
    }

}