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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\reporting\performance;

use context;
use context_user;
use core\output\notification;
use core\notification as alert_notification;
use mod_perform\controllers\perform_controller;
use mod_perform\entity\activity\element_identifier as element_identifier_entity;
use mod_perform\util;
use mod_perform\views\embedded_report_view;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_url;
use totara_mvc\has_report;
use totara_mvc\view;

class element_identifier extends perform_controller {

    use has_report;
    use renders_performance_reports;

    public function setup_context(): context {
        return util::get_default_context();
    }

    public function action() {
        global $USER;

        $element_identifier = $this->get_optional_param('element_identifier', null, PARAM_RAW);
        $element_identifier = preg_replace('/[^0-9,]/', '', $element_identifier);

        if (empty($element_identifier)) {
            $this->set_url(static::get_url());
            $link_url = new moodle_url('/mod/perform/reporting/performance/');
            return self::create_view('mod_perform/no_report', [
                'content' => view::core_renderer()->notification(
                    get_string('activity_report_no_params_warning_message', 'mod_perform', (object)['url' => $link_url->out(true)]),
                    notification::NOTIFY_WARNING
                )
            ]);
        }

        // Must be higher level admin to access this report.
        $this->require_capability('mod/perform:report_on_all_subjects_responses', context_user::instance($USER->id));

        $extra_data = [
            'element_identifier' => $element_identifier,
        ];

        $report = $this->load_embedded_report('perform_response_element_by_reporting_id', $extra_data);
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);

        $this->set_url(static::get_url($extra_data));

        // Names of element identifiers this report is being filtered by.
        $filtered_report_element_identifier_names = (element_identifier_entity::repository())
            ->filter_by_identifier_id(explode(',', $element_identifier))
            ->get()
            ->pluck('identifier');

        $reporting_id_banner_component = $this->get_reporting_id_banner_component($filtered_report_element_identifier_names);

        $filtered_count = $report->get_filtered_count();
        $action_card_component = $this->get_action_card(
            $report,
            export::SHORT_NAME_ELEMENT_IDENTIFIER,
            ['element_identifier_export_filter' => $element_identifier]
        );
        $data = [
            'action_card_component' => $action_card_component,
            'info_banner' => alert_notification::info(get_string('response_data_info_banner', 'mod_perform')),
            'reporting_id_banner_component' => $reporting_id_banner_component
        ];
        $heading = $this->get_heading($filtered_count, get_string('selected_reporting_ids', 'mod_perform'));

        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_perform/bulk_exportable_report')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($heading)
            ->set_back_to(...$this->get_back_to_by_content_tab())
            ->set_additional_data($data);

        $report_view->set_report_heading($this->get_report_heading($report, $report_view, $heading));
        return $report_view;
    }

    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/element_identifier.php';
    }

    private function get_reporting_id_banner_component(array $reporting_ids): string {
        return $this->get_rendered_component('mod_perform/components/report/performance/ReportingIdFilterBanner', [
            'reporting-ids' => $reporting_ids,
        ]);
    }
}
