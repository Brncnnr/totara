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

final class svggraph extends base {

    public static $translation = [
        'colors' => 'colours', // Used in first T13 upgrade only.
        'padding' => [
            '_default' => ['pad_top', 'pad_left', 'pad_bottom', 'pad_right'],
            'top' => 'pad_top',
            'left' => 'pad_left',
            'bottom' => 'pad_bottom',
            'right' => 'pad_right'
        ],
        'title' => [
            '_default' => 'graph_title',
            'text' => 'graph_title',
            'position' => 'graph_title_position',
            'font' => 'graph_title_font',
            'fontSize' => 'graph_title_font_size',
            'fontStyle' => 'graph_title_font_weight',
            'color' => 'graph_title_colour',
            'padding' => 'graph_title_space',
        ],
        'legend' => [
            'display' => 'show_legend',
            'position' => 'legend_position',
            'font' => 'legend_font',
            'fontSize' => 'legend_font_size',
            'fontStyle' => 'legend_font_weight',
            'color' => 'legend_colour',
            'padding' => 'legend_padding',
        ],
        'tooltips' => [
            'display' => 'show_tooltips',
            'backgroundColor' => 'tooltip_back_colour',
            'font' => 'tooltip_font',
            'fontSize' => 'tooltip_font_size',
            'fontStyle' => 'tooltip_font_weight',
            'color' => 'tooltip_colour',
            'borderRadius' => 'tooltip_round',
            'borderColor' => 'tooltip_colour',
            'borderWidth' => 'tooltip_stroke_width',
        ],
        'axis' => [
            'x' => [
                'display' => 'show_axis_v',
                'title' => [
                    '_default' => 'label_v',
                    'text' => 'label_v',
                    'font' => 'label_font_v',
                    'fontSize' => 'label_font_size_v',
                    'fontStyle' => 'label_font_weight_v',
                    'color' => 'label_colour_v',
                    'padding' => 'label_space',
                ],
                'grid' => [
                    'display' => 'show_grid_v',
                    'color' => 'grid_colour_v'
                ],
                'max' => 'axis_max_h'
            ],
            'y' => [
                'display' => 'show_axis_h',
                'title' => [
                    '_default' => 'label_h',
                    'text' => 'label_h',
                    'font' => 'label_font_h',
                    'fontSize' => 'label_font_size_h',
                    'fontStyle' => 'label_font_weight_h',
                    'color' => 'label_colour_h',
                    'padding' => 'label_space',
                ],
                'grid' => [
                    'display' => 'show_grid_h',
                    'color' => 'grid_colour_h'
                ],
                'max' => 'axis_max_v'
            ]
        ]
    ];

    /**
     * The list of derived settings for this chart -- if some other settings exist, add another setting
     * @var array
     */
    protected static $derived = [];

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

        foreach (self::$translation as $key => $value) {
            $options = array_merge($options, self::match($key, $settings, self::$translation));
        }

        // Set the derived settings
        foreach (self::$derived as $key => $value) {
            if (isset($options[$key])) {
                $options = array_merge($options, $value);
            }
        }

        // If there are custom settings, override the options with anything inside it
        if (isset($settings['custom'])) {
            $options = array_replace_recursive($options, $settings['custom']);
        }

        return $options;
    }
}
