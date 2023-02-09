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
use core\output\notification;
use core\notification as alert_notification;
use mod_perform\controllers\perform_controller;
use mod_perform\data_providers\activity\reportable_activities;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\util;
use mod_perform\views\embedded_report_view;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_exception;
use moodle_url;
use totara_mvc\has_report;
use totara_mvc\view;

class activity extends perform_controller {

    use has_report;
    use renders_performance_reports;

    /**
     * mod_perform\models\activity\activity instance
     * @var activity_model $activity
     */
    private $activity;

    public function setup_context(): context {
        // Whether the user can access this page is based on
        // whether there are any activities the user can report on
        // and not based on the context. The checks for this are based
        // on the users and not on the activiy.
        return util::get_default_context();
    }

    public function action() {
        $activity_id = $this->get_optional_param('activity_id', null, PARAM_INT);

        if ($activity_id === null) {
            $this->set_url(static::get_url());
            $link_url = new moodle_url('/mod/perform/reporting/performance/');
            return self::create_view('mod_perform/no_report', [
                'content' => view::core_renderer()->notification(
                    get_string('activity_report_no_params_warning_message', 'mod_perform', (object)['url' => $link_url->out(true)]),
                    notification::NOTIFY_WARNING
                )
            ]);
        }

        $reportable_activities = (new reportable_activities())->fetch()->get();
        if (!$reportable_activities->find('id', $activity_id)) {
            // Current user can't report on any subject users within this activity.
            throw new moodle_exception('error_activity_unavailable', 'mod_perform');
        }

        $activity_name = format_string($this->get_activity()->name);

        $extra_data = [
            'activity_id' => $activity_id,
        ];

        $this->set_url(static::get_url($extra_data));

        $report = $this->load_embedded_report('perform_response_element_by_activity', $extra_data);
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);

        $filtered_count = $report->get_filtered_count();

        $action_card_component = $this->get_action_card(
            $report,
            export::SHORT_NAME_ELEMENT,
            ['activity_id_export_filter' => $activity_id]
        );
        $heading = $this->get_heading($filtered_count, $activity_name);
        $data = [
            'action_card_component' => $action_card_component,
            'info_banner' => alert_notification::info(get_string('response_data_info_banner', 'mod_perform')),
        ];

        /** @var embedded_report_view $report_view */
        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_perform/bulk_exportable_report')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($heading)
            ->set_back_to(...$this->get_back_to_by_content_tab());
        $report_view->set_additional_data($data);

        $report_view->set_report_heading($this->get_report_heading($report, $report_view, $heading));
        return $report_view;
    }

    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/activity.php';
    }

    private function get_activity(): activity_model {
        if (!isset($this->activity)) {
            try {
                $activity_id = $this->get_required_param('activity_id', PARAM_INT);
                $this->activity = activity_model::load_by_id($activity_id);
            } catch (\Exception $e) {
                throw new moodle_exception('error_activity_id_wrong', 'mod_perform', '', null, $e);
            }
        }
        return $this->activity;
    }

}
