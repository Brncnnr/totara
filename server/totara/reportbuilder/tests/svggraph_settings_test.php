<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_reportbuilder
 */

use totara_reportbuilder\local\graph\settings\svggraph;
use totara_reportbuilder\local\graph\settings\base;

defined('MOODLE_INTERNAL') || die();

/**
 * This class tests creation of a ChartJS settings object from general settings
 *
 * @group totara_reportbuilder
 */
class totara_reportbuilder_svggraph_settings_testcase extends \basic_testcase {

    public function test_settings() {

        $settings = [
            'padding' => [
                'top' => 1,
                'left' => 2,
                'bottom' => 3,
                'right' => 4,
            ],
            'title' => [
                'text' => 'Test Title',
                'position' => 'top',
                'font' => 'Helvetica',
                'fontSize' => 5,
                'fontStyle' => 'bold',
                'color' => '#FF0000',
                'padding' => 6,
                'flange' => 'open' // Non-existent property -- should be ignored
            ],
            'legend' => [
                'display' => true,
                'position' => 'bottom',
                'font' => 'Times New Roman',
                'fontSize' => 7,
                'fontStyle' => 'bold',
                'color' => '#00FF00',
                'padding' => 8,
            ],
            'tooltips' => [
                'display' => true,
                'backgroundColor' => '#0000FF',
                'font' => 'Arial',
                'fontSize' => 9,
                'fontStyle' => 'bold',
                'color' => '#FFFF00',
                'borderRadius' => 10,
                'borderWidth' => 11,
            ],
            'axis' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'text' => 'Test X Axis Label',
                        'font' => 'Roboto',
                        'fontSize' => 12,
                        'fontStyle' => 'bold',
                        'color' => '#00FFFF',
                        'padding' => 13,
                    ],
                    'grid' => [
                        'display' => true,
                        'color' => '#FFFFFF'
                    ]
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'text' => 'Test Y Axis Label',
                        'font' => 'Roboto',
                        'fontSize' => 12,
                        'fontStyle' => 'bold',
                        'color' => '#00FFFF',
                    ],
                    'grid' => [
                        'display' => true,
                        'color' => '#FFFFFF'
                    ]
                ]
            ],
        ];

        $expected = [
            'colors' => base::get_default_colors(),
            'pad_top' => 1,
            'pad_left' => 2,
            'pad_bottom' => 3,
            'pad_right' => 4,
            'graph_title' => 'Test Title',
            'graph_title_position' => 'top',
            'graph_title_font' => 'Helvetica',
            'graph_title_font_size' => 5,
            'graph_title_font_weight' => 'bold',
            'graph_title_colour' => '#FF0000',
            'graph_title_space' => 6,
            'show_legend' => true,
            'legend_position' => 'bottom',
            'legend_font' => 'Times New Roman',
            'legend_font_weight' => 'bold',
            'legend_font_size' => 7,
            'legend_colour' => '#00FF00',
            'legend_padding' => 8,
            'show_tooltips' => true,
            'tooltip_back_colour' => '#0000FF',
            'tooltip_font' => 'Arial',
            'tooltip_font_size' => 9,
            'tooltip_font_weight' => 'bold',
            'tooltip_colour' => '#FFFF00',
            'tooltip_round' => 10,
            'tooltip_stroke_width' => 11,
            'show_axis_v' => true,
            'label_v' => 'Test X Axis Label',
            'label_font_v' => 'Roboto',
            'label_font_size_v' => 12,
            'label_font_weight_v' => 'bold',
            'label_colour_v' => '#00FFFF',
            'label_space' => 13,
            'show_grid_v' => true,
            'grid_colour_v' => '#FFFFFF',
            'show_axis_h' => true,
            'label_h' => 'Test Y Axis Label',
            'label_font_h' => 'Roboto',
            'label_font_size_h' => 12,
            'label_font_weight_h' => 'bold',
            'label_colour_h' => '#00FFFF',
            'show_grid_h' => true,
            'grid_colour_h' => '#FFFFFF'
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertEquals($expected, $chartsettings);

        // Test shortcut settings work correctly
        $settings = [
            'padding' => 10,
            'title' => 'Test Title',
            'axis' => [
                'x' => [
                    'title' => 'Test X Axis Title'
                ],
                'y' => [
                    'title' => 'Test Y Axis Title'
                ]
            ]
        ];

        $expected = [
            'colors' => base::get_default_colors(),
            'pad_top' => 10,
            'pad_left' => 10,
            'pad_bottom' => 10,
            'pad_right' => 10,
            'graph_title' => 'Test Title',
            'label_v' => 'Test X Axis Title',
            'label_h' => 'Test Y Axis Title'
        ];

        $chartsettings = svggraph::create($settings);
        $this->assertEquals($expected, $chartsettings);

        // Test that custom settings are applied, and override other generated settings correctly
        $settings = [
            'padding' => [
                'top' => 1,
            ],
            'custom' => [
                'pad_top' => 2,
                'auto_fit' => true
            ]
        ];

        $expected = [
            'colors' => base::get_default_colors(),
            'pad_top' => 2,
            'auto_fit' => true
        ];

        $chartsettings = svggraph::create($settings);
        $this->assertEquals($expected, $chartsettings);
    }

    public function test_colors() {
        $settings = [
            'colors' => ['red', 'green'],
        ];
        $expected = [
            'colors' => ['red', 'green'],
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colors' => 'red,#008000',
        ];
        $expected = [
            'colors' => ['red', '#008000'],
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colours' => ['red', 'green'],
        ];
        $expected = [
            'colors' => ['red', 'green'],
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colours' => 'red, green',
        ];
        $expected = [
            'colors' => ['red', 'green'],
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'custom' => ['colours' => 'red,green'],
        ];
        $expected = [
            'colors' => ['red', 'green'],
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colors' => ['#fff', '$#$', ['grrr'], true, false, ''],
        ];
        $expected = [
            'colors' => ['#fff', '$#$', ['grrr'], true, false, ''],
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colors' => ' ',
        ];
        $expected = [
            'colors' => base::get_default_colors(),
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [];
        $expected = [
            'colors' => base::get_default_colors(),
        ];
        $chartsettings = svggraph::create($settings);
        $this->assertSame($expected, $chartsettings);
    }

}