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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_mvc
 */

namespace totara_mvc;

use moodle_url;
use reportbuilder;
use totara_reportbuilder_renderer;

/**
 * Use this view to render a report.
 *
 * It collects all parts needed to display the report, like header, search, sidebar, the report itself, etc.
 * and passes it on to a template. The template can be customised for your need.
 *
 * The report view uses the totara_mvc/report mustache template by default. You can specify your own if you like in case you have
 * specific requirements.
 *
 * @package totara_mvc
 */
class report_view extends view {

    /**
     * @var array
     */
    private $back_to = [];

    /**
     * @var bool
     */
    private $debug;

    /**
     * report_view constructor.
     *
     * @param reportbuilder $report_builder
     * @param string $template name of the template to use, defaults to central report template
     * @param bool $debug
     */
    public function __construct(?string $template, reportbuilder $report_builder, bool $debug = false) {
        parent::__construct($template, $report_builder);
        $this->debug = $debug;
    }

    /**
     * @param reportbuilder $report_builder
     * @param bool $debug
     * @param string $template
     * @return report_view
     */
    public static function create_from_report(
        reportbuilder $report_builder,
        bool $debug = false,
        string $template = 'totara_mvc/report'
    ) {
        return new static($template, $report_builder, $debug);
    }

    /**
     * @param \reportbuilder $report
     * @return array
     */
    protected function prepare_output($report) {
        $this->set_button($report);

        /** @var totara_reportbuilder_renderer $report_renderer */
        $report_renderer = $this->get_page()->get_renderer('totara_reportbuilder');

        [$reporthtml, $debughtml] = $report_renderer->report_html($report, $this->debug);

        $template_data = [
            'backto'       => $this->get_back_to(),
            'heading'      => $this->get_heading($report, $report_renderer),
            'restrictions' => $this->get_restrictions($report),
            'description'  => $this->get_description($report, $report_renderer),
            'savedsearch'  => $this->get_saved_search_options($report),
            'search'       => $this->get_search($report),
            'sidebar'      => $this->get_sidebar($report),
            'showhide'     => $this->get_show_hide($report, $report_renderer),
            'export'       => $this->get_export($report, $report_renderer),
            'reporthtml'   => $reporthtml,
            'debughtml'    => $debughtml
        ];

        $report->include_js();

        return $template_data;
    }

    /**
     * Override to set custom button, defaults to edit button for report
     *
     * @param reportbuilder $report
     */
    protected function set_button(reportbuilder $report) {
        $this->get_page()->set_button($report->edit_button());
    }

    /**
     * Get description HTML snippet
     *
     * @param reportbuilder $report
     * @return string
     */
    private function get_restrictions(reportbuilder $report): string {
        return $this->capture_output(
            function () use ($report) {
                $report->display_restrictions();
            }
        );
    }

    /**
     * Get description HTML snuppet
     *
     * @param reportbuilder $report
     * @param totara_reportbuilder_renderer $renderer
     * @return string
     */
    private function get_description(reportbuilder $report, totara_reportbuilder_renderer $renderer): string {
        return $renderer->print_description($report->description, $report->get_id());
    }

    /**
     * Get saved search HTML snippet
     *
     * @param reportbuilder $report
     * @return string
     */
    private function get_saved_search_options(reportbuilder $report): string {
        return $this->capture_output(
            function () use ($report) {
                $report->display_saved_search_options();
            }
        );
    }

    /**
     * Get search HTML snippet
     *
     * @param reportbuilder $report
     * @return string
     */
    private function get_search(reportbuilder $report): string {
        return $this->capture_output(
            function () use ($report) {
                $report->display_search();
            }
        );
    }

    /**
     * Get sidebar HTML snippet
     *
     * @param reportbuilder $report
     * @return string
     */
    private function get_sidebar(reportbuilder $report): string {
        return $this->capture_output(
            function () use ($report) {
                $report->display_sidebar_search();
            }
        );
    }

    /**
     * Get export HTML snippet
     *
     * @param reportbuilder $report
     * @param totara_reportbuilder_renderer $renderer
     * @return string
     */
    private function get_export(reportbuilder $report, totara_reportbuilder_renderer $renderer): string {
        return $this->capture_output(
            function () use ($report, $renderer) {
                $renderer->export_select($report, $report->get_saved_search_id());
            }
        );
    }

    /**
     * Get heading HTML snippet
     *
     * @param reportbuilder $report
     * @param totara_reportbuilder_renderer $renderer
     * @return string
     */
    private function get_heading(reportbuilder $report, totara_reportbuilder_renderer $renderer): string {
        return $renderer->render_from_template(
            'totara_reportbuilder/report_heading',
            [
                'reportid' => $report->get_id(),
                'heading' => $this->get_report_title(),
                'fullname' => $report->fullname,
                'resultcount' => $renderer->result_count_info($report),
            ]
        );
    }

    /**
     * Create and return link back to another page if back_to was set previously
     *
     * @return string
     */
    private function get_back_to(): string {
        if (isset($this->back_to['url']) &&
            $this->back_to['url'] instanceof moodle_url &&
            !empty($this->back_to['text'])
        ) {
            $link = \html_writer::link($this->back_to['url'], $this->back_to['text']);
        }
        return $link ?? '';
    }

    /**
     * Set back to url for report
     *
     * @param moodle_url $url
     * @param string $text
     * @return report_view
     */
    public function set_back_to(moodle_url $url, string $text): report_view {
        $this->back_to = [
            'url' => $url,
            'text' => $text
        ];
        return $this;
    }

    /**
     * Returns HTML for a button that lets users show and hide report columns
     * interactively within the report
     *
     * @param reportbuilder $report
     * @param totara_reportbuilder_renderer $renderer
     * @return string
     */
    private function get_show_hide(reportbuilder $report, totara_reportbuilder_renderer $renderer): string {
        return $renderer->showhide_button($report);
    }

    /**
     * Return report title with multi language support
     *
     * @param bool $format supports multi language
     * @return string
     */
    public function get_report_title(bool $format = true): string {
        if ($format) {
            return format_string($this->get_title());
        }
        return $this->get_title();
    }
}
