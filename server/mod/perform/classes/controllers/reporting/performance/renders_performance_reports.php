<?php
/*
 * This file is part of Totara Perform
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\reporting\performance;

use mod_perform\rb\util as rb_util;
use mod_perform\views\embedded_report_view;
use moodle_url;
use reportbuilder;
use totara_mvc\renders_components;

trait renders_performance_reports {

    use renders_components;

    /**
     * @deprecated since Totara 17
     * @param int $filtered_count
     * @param string $search_hash
     * @param string $embedded_short_name
     * @param array $additional_export_href_params
     * @return string
     */
    protected function get_rendered_action_card(
        int $filtered_count,
        string $search_hash,
        string $embedded_short_name,
        array $additional_export_href_params
    ): string {
        debugging(
            'mod_perform\controllers\reporting\performance\renders_performance_reports::get_rendered_action_card() is deprecated,
            please use mod_perform\controllers\reporting\performance\renders_performance_reports::get_action_card()',
            DEBUG_DEVELOPER
        );
        // No results no card.
        if ($filtered_count === 0) {
            return '';
        }

        $action_card_props = [
            'row-count' => $filtered_count,
            'embedded-shortname' => $embedded_short_name,
            'filter-hash' => $search_hash, // Hash of current filtered state, ensures we're not using stale data.
            'export-row-limit' => self::BULK_EXPORT_MAX_ROWS,
            'additional-export-href-params' => $additional_export_href_params,
        ];

        return $this->get_rendered_component(
            'mod_perform/components/report/element_response/ExportActionCard', $action_card_props
        );
    }

    /**
     * Get rendered action card
     *
     * @param reportbuilder $report
     * @param string $embedded_short_name
     * @param array $additional_export_href_params
     * @return string
     */
    protected function get_action_card(
        \reportbuilder $report,
        string $embedded_short_name,
        array $additional_export_href_params
    ): string {
        $filtered_count = $report->get_filtered_count();
        // No results no card.
        if ($filtered_count === 0) {
            return '';
        }

        $action_card_props = [
            'row-count' => $filtered_count,
            'embedded-shortname' => $embedded_short_name,
            'filter-hash' => $report->get_search_hash(), // Hash of current filtered state, ensures we're not using stale data.
            'export-formats' => rb_util::export_for_props($report),
            'export-row-limit' => self::BULK_EXPORT_MAX_ROWS,
            'additional-export-href-params' => $additional_export_href_params,
        ];

        return $this->get_rendered_component(
            'mod_perform/components/report/element_response/ExportActionCard', $action_card_props
        );
    }

    protected function get_report_heading(reportbuilder $report, embedded_report_view $report_view, string $page_title): string {
        $report_renderer = $report_view->get_page()->get_renderer('totara_reportbuilder');

        // We want to replace the default report heading but want to keep any reporting amd, etc.
        // Thus making use of the existing report_heading template
        return $report_renderer->render_from_template(
            'totara_reportbuilder/report_heading',
            [
                'reportid' => $report->get_id(),
                'heading' => $page_title,
                'fullname' => $report->fullname,
            ]
        );
    }

    protected function get_heading(int $filtered_count, string $target): string {
        $heading_string_params = (object)[
            'target' => $target,
            'count' => $filtered_count,
        ];

        if ($filtered_count === 1) {
            return get_string('performance_data_for', 'mod_perform', $heading_string_params);
        }

        return get_string('performance_data_for_plural', 'mod_perform', $heading_string_params);
    }

    protected function get_back_to_user_tab(): array {
        return $this->get_back_to(activity_response_data::$by_user_tab_uri);
    }

    protected function get_back_to_by_content_tab(): array {
        return $this->get_back_to(activity_response_data::$by_content_tab_uri);
    }

    private function get_back_to(string $uri): array {
        $arrow =  $this->get_rendered_component('tui/components/icons/BackArrow', ['size' => 100]);
        $back_text = get_string('all_performance_data_records', 'mod_perform');
        $back_url = new moodle_url($uri);

        return [$back_url, $arrow . ' ' . $back_text];
    }

    /**
     * @param array $params
     * @param string $for
     * @return array
     */
    protected function get_back_to_activity(array $params, string $for): array {
        $back_url = new moodle_url('/mod/perform/reporting/performance/activity.php', $params);
        return $this->get_back_to_report($back_url, $for);
    }

    /**
     * @param array $params
     * @param string $for
     * @return array
     */
    protected function get_back_to_subject_user(array $params, string $for): array {
        $back_url = new moodle_url('/mod/perform/reporting/performance/user.php', $params);
        return $this->get_back_to_report($back_url, $for);
    }

    /**
     * @param array $params
     * @return array
     */
    protected function get_back_to_element_identifier(array $params): array {
        $back_url = new moodle_url('/mod/perform/reporting/performance/element_identifier.php', $params);
        return $this->get_back_to_report($back_url, get_string('selected_reporting_ids', 'mod_perform'));
    }

    /**
     * @param moodle_url $back_url
     * @param string $for
     * @return array
     */
    private function get_back_to_report(moodle_url $back_url, string $for): array {
        $arrow =  $this->get_rendered_component('tui/components/icons/BackArrow', ['size' => 100]);
        $back_text = get_string('back_to_report', 'mod_perform', $for);
        return [$back_url, $arrow . ' ' . $back_text];
    }
}
