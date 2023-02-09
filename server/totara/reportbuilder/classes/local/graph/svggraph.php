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

namespace totara_reportbuilder\local\graph;

/**
 * Abstraction for SVGGraph library for use with reports.
 */
final class svggraph extends base {
    /** @var array SVGGraph settings */
    protected $svggraphsettings;
    /** @var string SVGGraph type */
    protected $svggraphtype;
    /** @var array SVGGraph colours */
    protected $svggraphcolours;
    /** @var  array list of supported chart types */
    protected static $allowed_types = [
        'column',
        'line',
        'bar',
        'pie',
        'scatter',
        'area',
        'doughnut',
    ];

    protected function init(): void {
        parent::init();

        if ($this->graphrecord->type === '') {
            return;
        }

        $this->usersettings = settings\svggraph::create($this->usersettings);

        // Set up colours.
        $this->svggraphcolours = $this->usersettings['colors'];
        unset($this->usersettings['colors']);

        $this->svggraphsettings = [
            'preserve_aspect_ratio' => 'xMidYMid meet',
            'auto_fit' => true,
            'axis_font' => 'sans-serif',
            'pad_right' => 20,
            'pad_left' => 20,
            'pad_bottom' => 20,
            'axis_stroke_width' => 1,
            'axis_font_size' => 12,
            'axis_text_space' => 6,
            'show_grid' => false,
            'division_size' => 6,
            'stroke_width' => 0,
            'back_colour' => '#fff',
            'back_stroke_width' => 0,
            'marker_size' => 3,
            'line_stroke_width' => 2,
            'repeated_keys' => 'accept', // Bad luck, we cannot prevent repeated values.
            'label_font_size' => 14,
            // Custom Totara hacks.
            'label_shorten' => 40,
            'legend_shorten' => 80,
            'legend_entries' => []
        ];
    }

    protected function process_data(array $data): void {
        if ($this->category == base::GRAPH_CATEGORY_COLUMN) {
            $this->series[] = $this->processedcount;
            foreach ($data as $k => $val) {
                if (isset($this->legendcolumn) and $k === $this->legendcolumn) {
                    $this->svggraphsettings['legend_entries'][] = (string)$val;
                    continue;
                }
                if (!isset($this->values[$k])) {
                    continue;
                }
                $this->values[$k][$this->processedcount] = self::normalize_numeric_value($val);
            }
            $this->processedcount++;
            return;
        }

        $value = [];
        if ($this->category == base::GRAPH_CATEGORY_SIMPLE) {
            $value[base::GRAPH_CATEGORY_SIMPLE] = $this->processedcount + 1;
        } else {
            $value[$this->category] = $data[$this->category];
        }

        foreach ($this->series as $i => $key) {
            $val = $data[$i];
            $value[$i] = self::normalize_numeric_value($val);
        }

        $this->values[] = $value;
        $this->processedcount++;
    }

    /**
     * Render chart.
     *
     * @param int|null $width
     * @param int|null $height
     * @return string HTML markup
     */
    public function render(?int $width = null, ?int $height = null): string {
        $data = $this->get_render_data($width, $height);
        return self::render_data($data);
    }

    /**
     * Returns data for future rendering,
     * the result can be cached later.
     *
     * @param int|null $width width of the graph
     * @param int|null $height height of the graph
     * @return array data for rendering, must be compatible with json_encode
     */
    public function get_render_data(?int $width, ?int $height): array {
        $this->init_svggraph();
        if (!$this->svggraphtype) {
            // Nothing to do.
            return ['svg' => ''];
        }
        $settings = $this->get_final_settings();

        $renderwidth = isset($width) ? $width : 1000;
        $renderheight = isset($height) ? $height : 400;

        // Tweak colours.
        $colours = $this->svggraphcolours;
        if (count($this->series) == 1 and !$this->is_pie_chart()) {
            if (!empty($this->usersettings['colorRanges'])) {
                // Use colorRanges only for non-pie charts with one data series.
                $colorranges = $this->usersettings['colorRanges'];
                $colours = [];
                foreach ($this->values as $k => $data) {
                    $v = $data[1];
                    $ci = 0;
                    foreach ($colorranges as $boundary) {
                        if ($v < $boundary) {
                            break;
                        }
                        $ci++;
                    }
                    $ci = $ci % count($this->svggraphcolours);
                    $colours[] = $this->svggraphcolours[$ci];
                }
                // Hide legend if not explicitly shown because it cannot show the correct colur box.
                if (!isset($settings['show_legend'])) {
                    $settings['show_legend'] = false;
                }
            } else {
                // Set this to the first colour so a single series doesn't come out looking like a rainbow
                $colours = [$this->svggraphcolours[0]];
            }
        }

        $svggraph = new \SVGGraph($renderwidth, $renderheight, $settings);
        $svggraph->Colours($colours);
        $svggraph->Values($this->shorten_labels($this->values, $settings));
        $svg = $svggraph->Fetch($this->svggraphtype, false, false);

        if (empty($svg)) {
            return ['svg' => ''];
        }
        if (strpos($svg, 'Zero length axis (min >= max)') === false) {
            return ['svg' => $svg];
        }

        // Use a workaround to prevent axis problems caused by zero only values.
        $dir = ($this->graphrecord->type === 'bar') ? 'h' : 'v';
        if (!isset($settings['axis_min_' . $dir])) {
            $settings['axis_min_' . $dir] = 0;
        }
        if (!isset($settings['axis_max_' . $dir]) or $settings['axis_max_' . $dir] <= $settings['axis_min_' . $dir]) {
            $settings['axis_max_' . $dir] = $settings['axis_min_' . $dir] + 1;
        }
        $svggraph = new \SVGGraph($renderwidth, $renderheight, $settings);
        $svggraph->Colours($colours);
        $svggraph->Values($this->shorten_labels($this->values, $settings));

        $svg = $svggraph->Fetch($this->svggraphtype, false, false);
        $svg = self::fix_svg_rtl($svg);

        return ['svg' => $svg];
    }

    /**
     * Returns the rendered graph markup.
     *
     * @param array data from get_render_data()
     * @return string
     */
    public static function render_data(array $data): string {
        if (empty($data['svg'])) {
            return '';
        }
        return \html_writer::div($data['svg'], 'rb-report-svggraph');
    }

    protected function init_svggraph() {
        global $CFG;

        $this->svggraphtype = null;

        if ($this->count_records() == 0) {
            return;
        }

        if ($this->is_pie_chart()) {
            // Rework the structure because Pie graph may use only one series.
            $legend = [];
            foreach ($this->values as $value) {
                $legend[] = $value[$this->category];
            }
            $this->svggraphsettings['legend_entries'] = $legend;
            $this->svggraphsettings['show_labels'] = true;
            $this->svggraphsettings['show_label_key'] = false;
            $this->svggraphsettings['show_label_amount'] = false;
            $this->svggraphsettings['show_label_percent'] = true;

        } else {
            // Optionally remove empty series.
            if (!empty($this->usersettings['remove_empty_series'])) {
                if ($this->category >= 0) { // Normal category setup only!
                    foreach ($this->series as $i => $colkey) {
                        if ($i == $this->category) {
                            // Always keep te category item!
                            continue;
                        }
                        $nonzero = false;
                        foreach ($this->values as $j => $value) {
                            if ($value[$i] != 0) {
                                $nonzero = true;
                                break;
                            }
                        }
                        if ($nonzero) {
                            continue;
                        }
                        unset($this->series[$i]);
                        foreach ($this->values as $j => $value) {
                            unset($this->values[$j][$i]);
                        }
                    }
                }
            }

            if (empty($this->series)) {
                // Nothing to plot.
                return;
            }

            // Create legend items.
            if ($this->category != base::GRAPH_CATEGORY_COLUMN) {
                $legend = [];
                foreach ($this->series as $i => $colkey) {
                    $legend[] = $this->report->format_column_heading($this->report->columns[$colkey], true);
                }
                $this->svggraphsettings['legend_entries'] = $legend;
            }
        }
        unset($this->usersettings['remove_empty_series']);

        $this->svggraphsettings['structured_data'] = true;
        $this->svggraphsettings['structure'] = ['key' => $this->category, 'value' => array_keys($this->series)];
        $seriescount = count($this->series);
        $singleseries = ($seriescount === 1);

        if ($this->category == base::GRAPH_CATEGORY_SIMPLE) {
            // Row number as category - start with 1 instead of automatic 0.
            if ($this->graphrecord->type === 'bar') {
                $this->svggraphsettings['axis_min_v'] = 1;
            } else {
                $this->svggraphsettings['axis_min_h'] = 1;
            }
        }

        if ($this->graphrecord->type === 'bar') {
            if ($seriescount <= 2) {
                $this->svggraphsettings['bar_space'] = 10;
            } else if ($seriescount <= 4) {
                $this->svggraphsettings['bar_space'] = 7;
            } else {
                $this->svggraphsettings['bar_space'] = 5;
            }
            if ($singleseries) {
                $this->svggraphtype = 'HorizontalBarGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'HorizontalStackedBarGraph' : 'HorizontalGroupedBarGraph';
            }

        } else if ($this->graphrecord->type === 'line') {
            if ($singleseries) {
                $this->svggraphtype = 'MultiLineGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'StackedLineGraph' : 'MultiLineGraph';
            }

        } else if ($this->graphrecord->type === 'scatter') {
            if ($singleseries) {
                $this->svggraphtype = 'ScatterGraph';
            } else {
                $this->svggraphtype = 'MultiScatterGraph';
            }

        } else if ($this->graphrecord->type === 'area') {
            $this->svggraphsettings['fill_under'] = true;
            $this->svggraphsettings['marker_size'] = 2;

            if ($singleseries) {
                $this->svggraphtype = 'MultiLineGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'StackedLineGraph' : 'MultiLineGraph';
            }

        } else if ($this->graphrecord->type === 'pie') {
            $this->svggraphtype = 'PieGraph';

        } else if ($this->graphrecord->type === 'doughnut') {
            $this->svggraphtype = 'DonutGraph';
        } else { // Type 'column' or unknown.
            $this->graphrecord->type = 'column';
            if ($seriescount <= 2) {
                $this->svggraphsettings['bar_space'] = 10;
            } else if ($seriescount <= 5) {
                $this->svggraphsettings['bar_space'] = 15;
            } else if ($seriescount <= 10) {
                $this->svggraphsettings['bar_space'] = 20;
            } else {
                $this->svggraphsettings['bar_space'] = 25;
            }
            if ($singleseries) {
                $this->svggraphtype = 'BarGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'StackedBarGraph' : 'GroupedBarGraph';
            }
        }

        // Rotate data labels if necessary.
        if ($this->count_records() > 5 and !$this->is_pie_chart()) {
            if (get_string('thisdirectionvertical', 'core_langconfig') === 'btt') {
                $this->svggraphsettings['axis_text_angle_h'] = 90;
            } else {
                $this->svggraphsettings['axis_text_angle_h'] = -90;
            }
        }
    }

    protected function get_final_settings() {
        $settings = $this->svggraphsettings;

        foreach ($this->usersettings as $k => $v) {
            if ($k === 'type') {
                continue;
            }
            $settings[$k] = $v;
        }

        if (right_to_left()) {
            if (!isset($settings['legend_text_side'])) {
                $settings['legend_text_side'] = 'left';
            }
        }

        // Set up legend defaults and shorten entries if requested.
        $settings = $this->shorten_legend($settings);

        $settings = array_merge($settings, $this->usersettings);

        return $settings;
    }

    /**
     * Set up legend defaults and shorten entries if requested.
     *
     * By default if there are many entries the default settings are
     * adjusted to fix as much as possible into 3 columns max.
     *
     * @param array $settings
     * @return array modified $settings
     */
    protected function shorten_legend(array $settings) {
        if (empty($settings['legend_entries'])) {
            return $settings;
        }

        // If there are many legend entries make everything smaller and use more columns by default.
        if (!isset($settings['legend_entry_width']) and !isset($settings['legend_entry_height']) and !isset($settings['legend_columns'])) {
            $legendcount = count($settings['legend_entries']);
            if ($legendcount > 84) {
                $settings['legend_columns'] = 3;
                $settings['legend_entry_width'] = 6;
                $settings['legend_entry_height'] = 6;
                $settings['legend_font_size'] = 6;
            } else if ($legendcount > 28) {
                $settings['legend_columns'] = ceil($legendcount / 28);
                $settings['legend_entry_width'] = 7;
                $settings['legend_entry_height'] = 7;
                $settings['legend_font_size'] = 7;
            } else if ($legendcount > 21) {
                $settings['legend_columns'] = 1;
                $settings['legend_entry_width'] = 8;
                $settings['legend_entry_height'] = 8;
                $settings['legend_font_size'] = 8;
            } else if ($legendcount > 14) {
                $settings['legend_columns'] = 1;
                $settings['legend_entry_width'] = 10;
                $settings['legend_entry_height'] = 10;
                $settings['legend_font_size'] = 10;
            }
        }

        if (!empty($settings['legend_shorten'])) {
            $legendshorten = (int)$settings['legend_shorten'];
            if ($legendshorten > 0) {
                foreach ($settings['legend_entries'] as $k => $v) {
                    $settings['legend_entries'][$k] = shorten_text($v, $legendshorten);
                }
            }
        }

        return $settings;
    }

    /**
     * Shorten the label texts in graph.
     *
     * @param array $values
     * @param array $settings
     * @return array modified $values
     */
    protected function shorten_labels(array $values, array $settings) {
        $labelshorten = (int)$settings['label_shorten'];

        if ($labelshorten <= 0) {
            return $values;
        }

        $legendkey = $settings['structure']['key'];

        foreach ($values as $k => $v) {
            if (!isset($v[$legendkey])) {
                continue;
            }
            $values[$k][$legendkey] = shorten_text($v[$legendkey], $labelshorten);
        }

        return $values;
    }

    /**
     * Set all fonts used in svg graph to the specified font
     *
     * @param string $font Name of the font to use
     */
    public function set_font($font) {
        // this place require set all font settings for pdf svg graph, see svggraph.ini file
        $svgfonts = ['axis_font', 'tooltip_font', 'graph_title_font', 'legend_font', 'legend_title_font', 'data_label_font',
            'label_font', 'guideline_font', 'crosshairs_text_font', 'bar_total_font', 'inner_text_font'
        ];
        foreach ($svgfonts as $svgfont) {
            $this->svggraphsettings[$svgfont] = $font;
        }
    }

    /**
     * Try to fix the SVG data somehow to make it work with RTL languages.
     *
     * @param string $data
     * @param null|bool $rtl apply RTL hacks, NULL means detect RTL from current language
     * @return string SVG markup
     */
    private static function fix_svg_rtl($data, $rtl = null) {
        if ($rtl === null) {
            $rtl = right_to_left();
        }
        if (!$rtl) {
            return $data;
        }

        $data = str_replace('<svg ', '<svg direction="rtl" ', $data);

        $data = str_replace('text-anchor="end"', 'text-anchor="xxx"', $data);
        $data = str_replace('text-anchor="start"', 'text-anchor="end"', $data);
        $data = str_replace('text-anchor="xxx"', 'text-anchor="start"', $data);

        return $data;
    }

    /**
     * Normalise the value before sending to SVGGraph for display.
     *
     * Note: There is a lot of guessing in here.
     *
     * @param mixed $val
     * @return int|float|string
     */
    protected static function normalize_numeric_value($val) {
        // Strip the percentage sign, the SVGGraph is not compatible with it.
        if ($val !== null && substr($val, -1) === '%') {
            $val = substr($val, 0, -1);
        }

        // Trim spaces, they might be before the % for example, keep newlines though.
        if (is_string($val)) {
            $val = trim($val, ' ');
        }

        // Normalise decimal values to PHP format, SVGGraph needs to localise the numbers itself.
        if ($val !== null && substr_count($val, ',') === 1 and substr_count($val, '.') === 0) {
            $val = str_replace(',', '.', $val);
        }

        if ($val === null or $val === '' or !is_numeric($val)) {
            // There is no way to plot non-numeric data, sorry,
            // we need to use '0' because SVGGraph does not support nulls.
            $val = 0;
        } else if (is_string($val)) {
            if ($val === (string)(int)$val) {
                $val = (int)$val;
            } else {
                $val = (float)$val;
            }
        }

        return $val;
    }

    /**
     * Returns whether this is one of the pie chart variants
     * @return bool
     */
    private function is_pie_chart(): bool {
        return $this->graphrecord->type === 'pie'
            || $this->graphrecord->type === 'doughnut';
    }

    public static function get_name(): string {
        return get_string('graphlibsvggraph', 'totara_reportbuilder');
    }
}
