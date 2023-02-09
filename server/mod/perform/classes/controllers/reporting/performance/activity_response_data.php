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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\reporting\performance;

use context;
use core\entity\user;
use core\notification;
use mod_perform\controllers\perform_controller;
use mod_perform\util;
use mod_perform\views\embedded_report_view;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_url;
use required_capability_exception;
use totara_mvc\has_report;

class activity_response_data extends perform_controller {
    use activity_response_data_tabs;
    use has_report;

    /** @var string URL used in menu */
    public const URL =  '/mod/perform/reporting/performance/index.php';

    /**
     * @var string
     */
    private $current_tab;

    public function setup_context(): context {
        return util::get_default_context();
    }

    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/';
    }

    public function action() {
        return $this->action_by_user();
    }

    public function action_by_user() {
        $this->check_access();

        $this->current_tab = 'by_user';

        $url = static::get_base_url() . 'activity_responses_' . $this->current_tab . '.php';
        $this->set_url(new moodle_url($url));

        $data = [
            'info_banner' => notification::info(get_string('response_data_info_banner', 'mod_perform')),
            'tabs' => self::get_activity_response_data_tabs($this->current_tab),
            'page_heading' => get_string('performance_activity_response_data_heading', 'mod_perform'),
        ];
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);

        $report = $this->load_embedded_report('perform_response_user');

        $new_heading = get_string("subject_users_number_shown",
            'mod_perform', $report->get_filtered_count()
        );

        /** @var embedded_report_view $report_view */
        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_perform/performance_reporting')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($new_heading);
        $report_view->set_additional_data($data);
        $report_renderer = $report_view->get_page()->get_renderer('totara_reportbuilder');

        // We want to replace the default report heading but want to keep any reporting amd, etc.
        // Thus making use of the existing report_heading template
        $rendered_heading = $report_renderer->render_from_template(
            'totara_reportbuilder/report_heading',
            [
                'reportid' => $report->get_id(),
                'heading' => $new_heading,
                'fullname' => $report->fullname,
            ]
        );

        $report_view->set_report_heading($rendered_heading);

        return $report_view;
    }

    public function action_by_content() {
        $this->check_access();

        $this->current_tab = 'by_content';
        return $this->render_action_by_content();
    }

    private function check_access() {
        if (!util::can_potentially_report_on_subjects(user::logged_in()->id)) {
            throw new required_capability_exception($this->get_context(),
                'mod/perform:report_on_subject_responses', 'nopermissions', ''
            );
        }
    }

    private function render_action_by_content() {

        $url = static::get_base_url() . 'activity_responses_' . $this->current_tab . '.php';
        $this->set_url(new moodle_url($url));

        $has_reporting_id_access = util::has_report_on_all_subjects_capability($this->currently_logged_in_user()->id);

        $data = [
            'info_banner'  => notification::info(get_string('response_data_info_banner', 'mod_perform')),
            'tabs'         => self::get_activity_response_data_tabs($this->current_tab),
            'page_heading' => get_string('performance_activity_response_data_heading', 'mod_perform'),
            'content'      => self::create_tui_view('mod_perform/components/report/performance/ResponseByContent', [
                'has-reporting-ids' => $has_reporting_id_access,
            ]),
        ];

        return self::create_view('mod_perform/performance_report_by_content', $data)
            ->set_title($data['page_heading']);
    }
}
