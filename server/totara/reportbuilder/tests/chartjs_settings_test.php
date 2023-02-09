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

use totara_reportbuilder\local\graph\settings\chartjs;
use totara_reportbuilder\local\graph\settings\base;

defined('MOODLE_INTERNAL') || die();

/**
 * This class tests creation of a ChartJS settings object from general settings
 *
 * @group totara_reportbuilder
 */
class totara_reportbuilder_chartjs_settings_testcase extends \basic_testcase {

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
                'borderColor' => '#FF00FF',
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
                        'padding' => 13,
                    ],
                    'grid' => [
                        'display' => true,
                        'color' => '#FFFFFF'
                    ]
                ]
            ],
            'custom' => [
                'tooltips' => [
                    'titleFontFamily' => 'Open Sans', // Verify that custom settings override existing ones
                    'intersect' => true, // verify new settings can be passed through
                ]
            ]
        ];

        $expected = [
            'colors' => base::get_default_colors(),
            'layout' => [
                'padding' => [
                    'top' => 1,
                    'left' => 2,
                    'bottom' => 3,
                    'right' => 4
                ]
            ],
            'title' => [
                'display' => true,
                'text' => 'Test Title',
                'position' => 'top',
                'fontFamily' => 'Helvetica',
                'fontSize' => 5,
                'fontStyle' => 'bold',
                'fontColor' => '#FF0000',
                'padding' => 6
            ],
            'legend' => [
                'display' => true,
                'position' => 'bottom',
                'labels' => [
                    'fontFamily' => 'Times New Roman',
                    'fontSize' => 7,
                    'fontStyle' => 'bold',
                    'fontColor' => '#00FF00',
                    'padding' => 8
                ],
            ],
            'tooltips' => [
                'enabled' => true,
                'backgroundColor' => '#0000FF',
                'bodyFontFamily' => 'Arial',
                'bodyFontSize' => 9,
                'bodyFontStyle' => 'bold',
                'bodyFontColor' => '#FFFF00',
                'titleFontFamily' => 'Open Sans', // <-- this should be overridden in the custom field
                'titleFontSize' => 9,
                'titleFontStyle' => 'bold',
                'titleFontColor' => '#FFFF00',
                'footerFontFamily' => 'Arial',
                'footerFontSize' => 9,
                'footerFontStyle' => 'bold',
                'footerFontColor' => '#FFFF00',
                'cornerRadius' => 10,
                'borderColor' => '#FF00FF',
                'borderWidth' => 11,
                'intersect' => true // added via custom
            ],
            'scales' => [
                'xAxes' => [
                    [
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => 'Test X Axis Label',
                            'fontFamily' => 'Roboto',
                            'fontSize' => 12,
                            'fontStyle' => 'bold',
                            'fontColor' => '#00FFFF',
                            'padding' => 13
                        ],
                        'gridLines' => [
                            'drawBorder' => true,
                            'display' => true,
                            'color' => '#FFFFFF'
                        ]
                    ]
                ],
                'yAxes' => [
                    [
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => 'Test Y Axis Label',
                            'fontFamily' => 'Roboto',
                            'fontSize' => 12,
                            'fontStyle' => 'bold',
                            'fontColor' => '#00FFFF',
                            'padding' => 13
                        ],
                        'gridLines' => [
                            'drawBorder' => true,
                            'display' => true,
                            'color' => '#FFFFFF'
                        ]
                    ]
                ]
            ]
        ];
        $chartsettings = chartjs::create($settings);
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
            'layout' => [
                'padding' => 10
            ],
            'title' => [
                'display' => true,
                'text' => 'Test Title'
            ],
            'scales' => [
                'xAxes' => [
                    [
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => 'Test X Axis Title'
                        ]
                    ]
                ],
                'yAxes' => [
                    [
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => 'Test Y Axis Title'
                        ]
                    ]
                ]
            ]
        ];

        $chartsettings = chartjs::create($settings);
        $this->assertEquals($expected, $chartsettings);
    }

    public function test_colors() {
        $settings = [
            'colors' => ['red', 'green'],
        ];
        $expected = [
            'colors' => ['#FF0000', '#008000'],
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colors' => 'red,#008000',
        ];
        $expected = [
            'colors' => ['#FF0000', '#008000'],
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colours' => ['red', 'green'],
        ];
        $expected = [
            'colors' => ['#FF0000', '#008000'],
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colours' => 'red, green',
        ];
        $expected = [
            'colors' => ['#FF0000', '#008000'],
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'custom' => ['colours' => 'red,green'],
        ];
        $expected = [
            'colors' => ['#FF0000', '#008000'],
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colors' => ['#fff', '$#$', ['grrr'], true, false, ''],
        ];
        $expected = [
            'colors' => ['#fff', base::INVALID_COLOR, base::INVALID_COLOR, base::INVALID_COLOR, base::INVALID_COLOR, base::INVALID_COLOR],
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [
            'colors' => ' ',
        ];
        $expected = [
            'colors' => base::get_default_colors(),
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);

        $settings = [];
        $expected = [
            'colors' => base::get_default_colors(),
        ];
        $chartsettings = chartjs::create($settings);
        $this->assertSame($expected, $chartsettings);
    }
}
