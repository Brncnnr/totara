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

namespace totara_reportbuilder\local\graph\settings;

final class chartjs extends base {

    protected static $translation = [
        'padding' => [
            '_default' => 'layout.padding',
            'top' => 'layout.padding.top',
            'left' => 'layout.padding.left',
            'bottom' => 'layout.padding.bottom',
            'right' => 'layout.padding.right'
        ],
        'title' => [
            '_default' => 'title.text',
            'text' => 'title.text',
            'position' => 'title.position',
            'font' => 'title.fontFamily',
            'fontSize' => 'title.fontSize',
            'fontStyle' => 'title.fontStyle',
            'color' => 'title.fontColor',
            'padding' => 'title.padding',
        ],
        'legend' => [
            'display' => 'legend.display',
            'position' => 'legend.position',
            'font' => 'legend.labels.fontFamily',
            'fontSize' => 'legend.labels.fontSize',
            'fontStyle' => 'legend.labels.fontStyle',
            'color' => 'legend.labels.fontColor',
            'padding' => 'legend.labels.padding',
        ],
        'tooltips' => [
            'display' => 'tooltips.enabled',
            'backgroundColor' => 'tooltips.backgroundColor',
            'font' => ['tooltips.bodyFontFamily', 'tooltips.titleFontFamily', 'tooltips.footerFontFamily'],
            'fontSize' => ['tooltips.bodyFontSize', 'tooltips.titleFontSize', 'tooltips.footerFontSize'],
            'fontStyle' => ['tooltips.bodyFontStyle', 'tooltips.titleFontStyle', 'tooltips.footerFontStyle'],
            'color' => ['tooltips.bodyFontColor', 'tooltips.titleFontColor', 'tooltips.footerFontColor'],
            'borderRadius' => 'tooltips.cornerRadius',
            'borderColor' => 'tooltips.borderColor',
            'borderWidth' => 'tooltips.borderWidth',
        ],
        'axis' => [
            'x' => [
                'display' => 'gridLines.drawBorder',
                'title' => [
                    '_default' => 'scaleLabel.labelString',
                    'text' => 'scaleLabel.labelString',
                    'font' => 'scaleLabel.fontFamily',
                    'fontSize' => 'scaleLabel.fontSize',
                    'fontStyle' => 'scaleLabel.fontStyle',
                    'color' => 'scaleLabel.fontColor',
                    'padding' => 'scaleLabel.padding',
                ],
                'grid' => [
                    'display' => 'gridLines.display',
                    'color' => 'gridLines.color'
                ],
                'max' => 'ticks.max'
            ],
            'y' => [
                'display' => 'gridLines.drawBorder',
                'title' => [
                    '_default' => 'scaleLabel.labelString',
                    'text' => 'scaleLabel.labelString',
                    'font' => 'scaleLabel.fontFamily',
                    'fontSize' => 'scaleLabel.fontSize',
                    'fontStyle' => 'scaleLabel.fontStyle',
                    'color' => 'scaleLabel.fontColor',
                    'padding' => 'scaleLabel.padding',
                ],
                'grid' => [
                    'display' => 'gridLines.display',
                    'color' => 'gridLines.color'
                ],
                'max' => 'ticks.max'
            ]
        ]
    ];

    /**
     * The list of derived settings for this chart -- if some other settings exist, add another setting
     * @var array
     */
    protected static $derived = [
        'title.text'             => ['title.display' => true],
        'scaleLabel.labelString' => ['scaleLabel.display' => true],
    ];

    public static function create(array $settings): array {
        $options = [];

        // Normalise colors or use defaults.
        if (!empty($settings['colors'])) {
            $options['colors'] = static::parse_colors($settings['colors']);
        } else if (!empty($settings['colours'])) {
            $options['colors'] = static::parse_colors($settings['colours']);
        } else if (!empty($settings['custom']['colours'])) {
            // BC for sites upgraded to 13.0 and 13.1 earlier.
            $options['colors'] = static::parse_colors($settings['custom']['colours']);
        }
        if (empty($options['colors'])) {
            $options['colors'] = self::get_default_colors();
        }
        unset($settings['colors']);
        unset($settings['colours']);
        unset($settings['custom']['colours']);

        if (!$settings) {
            // Skip the rest of setting processing.
            return $options;
        }

        // Add color ranges.
        if (!empty($settings['colorRanges']) and is_array($settings['colorRanges'])) {
            $options['colorRanges'] = $settings['colorRanges'];
        }
        unset($settings['colorRanges']);

        // Add special type specific configuration options.
        if (!empty($settings['type'])) {
            $options['type'] = $settings['type'];
        }
        unset($settings['type']);

        $setting_list = [];

        // We can't just iterate over all options, since we have to treat 'axis' specially.
        $setting_list = array_merge($setting_list, self::match('padding', $settings, self::$translation));
        $setting_list = array_merge($setting_list, self::match('title', $settings, self::$translation));
        $setting_list = array_merge($setting_list, self::match('legend', $settings, self::$translation));
        $setting_list = array_merge($setting_list, self::match('tooltips', $settings, self::$translation));

        // Set the derived settings
        foreach (self::$derived as $key => $value) {
            if (isset($setting_list[$key])) {
                $setting_list = array_merge($setting_list, $value);
            }
        }

        // Convert the settings list into an object.
        foreach ($setting_list as $key => $value) {
            self::keys_to_object($key, $value, $options);
        }

        // Now do the axis settings, and add it to the created object
        if (isset($settings['axis'])) {
            $x_axis = self::match('x', $settings['axis'], self::$translation['axis']);
            $y_axis = self::match('y', $settings['axis'], self::$translation['axis']);

            // Check for derived settings in each axis
            foreach (self::$derived as $key => $value) {
                if (isset($x_axis[$key])) {
                    $x_axis = array_merge($x_axis, $value);
                }
                if (isset($y_axis[$key])) {
                    $y_axis = array_merge($y_axis, $value);
                }
            }


            $x_axis_obj = [];
            foreach ($x_axis as $key => $value) {
                self::keys_to_object($key, $value, $x_axis_obj);
            }

            $y_axis_obj = [];
            foreach ($y_axis as $key => $value) {
                self::keys_to_object($key, $value, $y_axis_obj);
            }

            $options['scales'] = [
                'xAxes' => [
                    $x_axis_obj
                ],
                'yAxes' => [
                    $y_axis_obj
                ]
            ];
        }

        // Add data options as a separate top level setting type
        if (array_key_exists('data-settings', $settings)) {
            $options['series-settings'] = $settings['data-settings'];
        }

        // If there are custom settings, override the options with anything inside it
        if (isset($settings['custom'])) {
            $options = array_replace_recursive($options, $settings['custom']);
        }

        return $options;
    }

    /**
     * Performs a deep merge of compiled ChartJS settings
     *
     * @param $settings1 array
     * @param $settings2 array
     *
     * @return array merged arrays
     */
    public static function merge(array $settings1, array $settings2): array {
        $merged = array_replace_recursive($settings1, $settings2);

        // Special treatment for scales -- the array merge will append each version of the array together,
        // but we want to replace it
        if (isset($merged['scales'])) {
            $x_axes = [];
            foreach ($merged['scales']['xAxes'] as $axis) {
                $x_axes = array_merge($x_axes, $axis);
            }

            $y_axes = [];
            foreach ($merged['scales']['yAxes'] as $axis) {
                $y_axes = array_merge($y_axes, $axis);
            }

            $merged['scales']['xAxes'] = [$x_axes];
            $merged['scales']['yAxes'] = [$y_axes];
        }

        return $merged;
    }

    private static function keys_to_object($key, $value, &$parent) {
        $dot_index = strpos($key, '.');
        if ($dot_index === false) {
            $parent[$key] = $value;
            return;
        }

        //Divide the key to it's new layers
        $components = explode('.', $key, 2);
        $level = $components[0];
        if (!isset($parent[$level])) {
            $parent[$level] = [];
        }
        self::keys_to_object($components[1], $value, $parent[$level]);
    }

    /**
     * Parse colors to array.
     *
     * @param string|array $colors
     * @return array
     */
    public static function parse_colors($colors): array {
        $colors = parent::parse_colors($colors);

        foreach ($colors as $k => $v) {
            if (!is_string($v)) {
                // Complex SVGGraph color settings are not supported here.
                $colors[$k] = self::INVALID_COLOR;
            } else {
                // Accept only valid CSS colours here.
                $color = purify_css_color($v);
                if ($color === false) {
                    $colors[$k] = self::INVALID_COLOR;
                } else {
                    $colors[$k] = $color;
                }
            }
        }

        return $colors;
    }
}
