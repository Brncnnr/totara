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
 * @author Fabian Derschatta <nathan.lewis@totaralearning.com>
 * @package performelement_multi_choice_single
 */

use core\format;
use mod_perform\formatter\activity\element_data_field_formatter;
use mod_perform\models\activity\element_plugin;
use performelement_multi_choice_single\formatter\data_field_formatter;
use performelement_multi_choice_single\multi_choice_single;

/**
 * @group perform
 * @group perform_element
 */
class performelement_multi_choice_single_data_field_formatter_testcase extends advanced_testcase {

    public function test_format() {
        global $CFG;
        require_once($CFG->libdir . '/filterlib.php');

        /** @var multi_choice_single $plugin */
        $plugin = element_plugin::load_by_plugin('multi_choice_single');

        // Initiate through main class
        $formatter_string = element_data_field_formatter::for_plugin($plugin);
        $this->assertEquals(data_field_formatter::class, $formatter_string);

        $context = context_system::instance();
        $format = format::FORMAT_PLAIN;

        $formatter = new data_field_formatter($format, $context);

        $data = [
            'options' => [
                [
                    'name' => 'option_0',
                    'value' => 'Simple string'
                ]
            ]
        ];
        $data = json_encode($data);
        $result = $formatter->format($data);
        $this->assertEquals($data, $result);

        // Multi lang without filter
        $data = [
            'options' => [
                [
                    'name' => 'option_0',
                    'value' => '<span lang="en" class="multilang">content English 1</span>'.
                        '<span lang="de" class="multilang">content German 1</span>'
                ],
                [
                    'name' => 'option_0',
                    'value' => '<span lang="en" class="multilang">content English 2</span>'.
                        '<span lang="de" class="multilang">content German 2</span>'
                ]
            ]
        ];

        $expected = [
            'options' => [
                [
                    'name' => 'option_0',
                    'value' => 'content English 1content German 1'
                ],
                [
                    'name' => 'option_0',
                    'value' => 'content English 2content German 2'
                ]
            ]
        ];

        $result = $formatter->format(json_encode($data));
        $this->assertEquals(json_encode($expected), $result);

        // Enable multi-language filter
        filter_manager::reset_caches();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);

        $expected = [
            'options' => [
                [
                    'name' => 'option_0',
                    'value' => 'content English 1'
                ],
                [
                    'name' => 'option_0',
                    'value' => 'content English 2'
                ]
            ]
        ];

        $result = $formatter->format(json_encode($data));
        $this->assertEquals(json_encode($expected), $result);

        // non-matching string will be returned as is
        $data = '<span lang="en" class="multilang">content English 2</span>'.
            '<span lang="de" class="multilang">content German 2</span>';
        $result = $formatter->format($data);
        $this->assertEquals($data, $result);
    }

}