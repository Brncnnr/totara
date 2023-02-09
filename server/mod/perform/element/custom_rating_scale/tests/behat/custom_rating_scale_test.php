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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use performelement_custom_rating_scale\custom_rating_scale;

/**
 * @group perform
 */
class custom_rating_scale_testcase extends advanced_testcase {

    public function test_format_response_lines(): void {
        $custom_rating_scale = custom_rating_scale::load_by_plugin('custom_rating_scale');
        $response = 'option_0';

        $element_data = [
            'options' => [
                [
                    'name' => 'option_0',
                    'value' => [
                        'text' => 'One',
                        'score' => '1',
                    ]
                ]
            ]
        ];

        $lines = $custom_rating_scale->format_response_lines(json_encode($response), json_encode($element_data));
        self::assertCount(1, $lines);
        self::assertEquals('One (score: 1)', $lines[0]);

        $lines = $custom_rating_scale->format_response_lines(json_encode(null), json_encode($element_data));
        self::assertCount(0, $lines);
    }

}