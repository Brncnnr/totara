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
use mod_perform\util;
use mod_perform\views\embedded_report_view;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_url;
use totara_mvc\has_report;
use totara_mvc\view;

class user extends perform_controller {

    use has_report;
    use renders_performance_reports;

    public function setup_context(): context {
        return util::get_default_context();
    }

    public function action() {
        $subject_user_id = $this->get_optional_param('subject_user_id', null, PARAM_INT);

        if (is_null($subject_user_id)) {
            $this->set_url(static::get_url());
            $link_url = new moodle_url('/mod/perform/reporting/performance/');
            return self::create_view('mod_perform/no_report', [
                'content' => view::core_renderer()->notification(
                    get_string(
                        'activity_report_no_params_warning_message',
                        'mod_perform',
                        (object) ['url' => $link_url->out(true)]
                    ),
                    notification::NOTIFY_WARNING
                )
            ]);
        }

        if (!util::can_report_on_user($subject_user_id, $this->currently_logged_in_user()->id)) {
            // Current user can't report on this subject user.
            $this->set_url(static::get_url());
            return self::create_view(null, view::core_renderer()->notification(
                get_string('error_user_unavailable', 'mod_perform'), notification::NOTIFY_ERROR
            ));
        }

        $extra_data = [
            'subject_user_id' => $subject_user_id,
        ];

        // Shortname of embedded report being used to generate list of items.
        $report_shortname = 'perform_response_subject_instance';

        $report = $this->load_embedded_report($report_shortname, $extra_data);

        $debug = $this->get_optional_param('debug', 0, PARAM_INT);

        $this->set_url(static::get_url($extra_data));

        // Current filtered count
        $filtered_count = $report->get_filtered_count();

        $action_card_component = $this->get_action_card(
            $report,
            export::SHORT_NAME_SUBJECT_INSTANCE,
            ['subject_user_id_export_filter' => $subject_user_id]
        );

        $subject_user = \core_user::get_user($subject_user_id);
        $subject_user_name = fullname($subject_user);
        $heading = $this->get_heading($filtered_count, $subject_user_name);
        $data = [
            'action_card_component' => $action_card_component,
            'info_banner' => alert_notification::info(get_string('response_data_info_banner', 'mod_perform')),
        ];

        /** @var embedded_report_view $report_view */
        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_perform/bulk_exportable_report')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($heading)
            ->set_back_to(...$this->get_back_to_user_tab());
        $report_view->set_additional_data($data);

        $report_view->set_report_heading($this->get_report_heading($report, $report_view, $heading));

        return $report_view;
    }

    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/user.php';
    }
}
