<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\reporting\performance;

use coding_exception;
use context;
use core\output\notification;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\activity\element;
use mod_perform\util;
use mod_perform\views\embedded_report_view;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_exception;
use moodle_url;
use totara_mvc\has_report;
use totara_mvc\view;

class response_data extends perform_controller {

    use has_report;
    use renders_performance_reports;

    public const SHORT_NAME_ELEMENT_IDENTIFIER = 'element_identifier';
    public const SHORT_NAME_ELEMENT = 'element';
    public const SHORT_NAME_SUBJECT_INSTANCE = 'subject_instance';

    /**
     * mod_perform\models\activity\activity instance
     * @var activity $activity
     */
    private $activity;

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        // The reports have their own checks whether they can export
        // therefore we are not checking the activity context here.
        return util::get_default_context();
    }
    /**
     * This is only reached if no action param is passed - display a message indicating parameters are required.
     */
    public function action() {
        $this->set_url(static::get_url());
        $link_url = new moodle_url('/mod/perform/reporting/performance/');
        return self::create_view('mod_perform/no_report', [
            'content' => view::core_renderer()->notification(
                get_string('bulk_response_data_no_params_warning_message', 'mod_perform', (object)['url' => $link_url->out(true)]),
                notification::NOTIFY_WARNING
            )
        ]);
    }

    /**
     * Returns 'perform_response_data' for subject_user|activity|element|subject_instance single id
     *
     * @return embedded_report_view
     * @throws moodle_exception
     */
    public function action_item() {
        // Basic check for the right permissions. Per row filtering done in report query.
        if (!util::can_potentially_report_on_subjects($this->currently_logged_in_user()->id)) {
            throw new moodle_exception('error_permission_missing', 'mod_perform');
        }

        $debug = $this->get_optional_param('debug', 0, PARAM_INT);
        $activity_id = $this->get_optional_param('activity_id', null, PARAM_INT);
        $subject_user_id = $this->get_optional_param('subject_user_id', null, PARAM_INT);
        $subject_instance_id = $this->get_optional_param('subject_instance_id', null, PARAM_INT);
        $element_id = $this->get_optional_param('element_id', null, PARAM_INT);

        $extra_data = compact('activity_id', 'subject_user_id', 'subject_instance_id', 'element_id');

        $this->set_url(static::get_url(array_merge(['action' => 'item'], $extra_data)));

        // This triggers the export because export and format params are set.
        $report = $this->load_embedded_report('perform_response_data', $extra_data, true, true);
        if ($subject_user_id) {
            [$heading_name, $back_to] = $this->action_subject_user_item($subject_user_id);
        } else if ($activity_id) {
            [$heading_name, $back_to] = $this->action_activity_item($activity_id);
        } else if ($element_id) {
            [$heading_name, $back_to] = $this->action_element_item($element_id);
        } else if ($subject_instance_id) {
            [$heading_name, $back_to] = $this->action_subject_instance_item($subject_instance_id);
        } else {
            throw new moodle_exception('response_data_type_incorrect', 'mod_perform');
        }
        $heading = $this->get_heading($report->get_filtered_count(), $heading_name);

        /** @var embedded_report_view $report_view */
        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_perform/response_data_reporting')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($heading)
            ->set_back_to(...$back_to);

        $report_view->set_report_heading($this->get_report_heading($report, $report_view, $heading));

        return $report_view;
    }

    /**
     * Returns 'perform_response_data' for subject_user|activity|element|subject_instance bulk params
     *
     * @return embedded_report_view|view
     * @throws \dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function action_bulk() {
        global $DB;

        // Basic check for the right permissions. Per row filtering done in report query.
        if (!util::can_potentially_report_on_subjects($this->currently_logged_in_user()->id)) {
            throw new moodle_exception('error_permission_missing', 'mod_perform');
        }

        $debug = $this->get_optional_param('debug', 0, PARAM_INT);
        $report_type = $this->get_required_param('filtered_report_export_type', PARAM_ALPHAEXT);
        $filtered_report_filter_hash = $this->get_required_param('filtered_report_filter_hash', PARAM_ALPHANUM);
        $url_params = [
            'action' => 'bulk',
            'filtered_report_export_type' => $report_type,
            'filtered_report_filter_hash' => $filtered_report_filter_hash
        ];

        switch ($report_type) {
            case self::SHORT_NAME_ELEMENT_IDENTIFIER:
                $source_shortname = 'perform_response_element_by_reporting_id';
                break;
            case self::SHORT_NAME_ELEMENT:
                $source_shortname = 'perform_response_element_by_activity';
                break;
            case self::SHORT_NAME_SUBJECT_INSTANCE:
                $source_shortname = 'perform_response_subject_instance';
                break;
            default:
                throw new moodle_exception('bulk_response_data_type_incorrect', 'mod_perform');
        }

        // Pass prevent_export=true here to stop has_report trait exporting this report.
        $report = $this->load_embedded_report($source_shortname, [], true, true);
        [$sql, $params] = $report->build_query(false, true, false);

        $data = $DB->get_records_sql($sql, $params, 0, self::BULK_EXPORT_MAX_ROWS);
        $ids = array_map(function ($item) {
            return $item->id;
        }, $data);

        switch ($report_type) {
            case self::SHORT_NAME_ELEMENT:
                if (!empty($ids)) {
                    $extra_data['element_id'] = $ids;
                }
                $extra_data['activity_id'] = $this->get_required_param('activity_id_export_filter', PARAM_INT);
                $url_params['activity_id_export_filter'] = $extra_data['activity_id'];
                $activity = activity::load_by_id($extra_data['activity_id']);
                $heading_name = format_string($activity->name);
                $back_to = $this->get_back_to_activity(['activity_id' => $extra_data['activity_id']], $heading_name);
                break;
            case self::SHORT_NAME_SUBJECT_INSTANCE:
                if (!empty($ids)) {
                    $extra_data['subject_instance_id'] = $ids;
                }
                $extra_data['subject_user_id'] = $this->get_required_param('subject_user_id_export_filter', PARAM_INT);
                $url_params['subject_user_id_export_filter'] = $extra_data['subject_user_id'];
                $subject_user = \core_user::get_user($extra_data['subject_user_id']);
                $heading_name = fullname($subject_user);
                $back_to = $this->get_back_to_subject_user(['subject_user_id' => $extra_data['subject_user_id']], $heading_name);
                break;
            case self::SHORT_NAME_ELEMENT_IDENTIFIER:
                if (!empty($ids)) {
                    $extra_data['element_id'] = $ids;
                }
                $identifiers = $this->get_required_param('element_identifier_export_filter', PARAM_TEXT);
                $url_params['element_identifier_export_filter'] = $identifiers;
                $identifiers = explode(',', $identifiers);
                foreach ($identifiers as $reporting_id) {
                    if (!is_number($reporting_id)) {
                        throw new coding_exception('Integer Reporting identifier IDs expected');
                    }
                }
                $extra_data['element_identifier'] = $identifiers;
                $heading_name = '';
                $back_to = $this->get_back_to_element_identifier(
                    ['element_identifier' => $url_params['element_identifier_export_filter']],
                );
                break;
            default:
                throw new moodle_exception('bulk_response_data_shortname_incorrect', 'mod_perform');
        }

        $this->set_url(static::get_url($url_params));
        $report = $this->load_embedded_report('perform_response_data', $extra_data, true, true);

        $heading = $this->get_heading($report->get_filtered_count(), $heading_name);

        /** @var embedded_report_view $report_view */
        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_perform/response_data_reporting')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($heading)
            ->set_back_to(...$back_to);

        $report_view->set_report_heading($this->get_report_heading($report, $report_view, $heading));

        return $report_view;
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/response_data.php';
    }

    /**
     * @param int $subject_user_id
     * @return array
     */
    private function action_subject_user_item(int $subject_user_id): array {
        $subject_user = \core_user::get_user($subject_user_id);
        $heading_name = fullname($subject_user);
        $back_to = $this->get_back_to_user_tab();
        return [$heading_name, $back_to];
    }

    /**
     * @param int $activity_id
     * @return array
     */
    private function action_activity_item(int $activity_id): array {
        $activity = activity::load_by_id($activity_id);
        $heading_name = format_string($activity->name);
        $back_to = $this->get_back_to_by_content_tab();
        return [$heading_name, $back_to];
    }

    /**
     * @param int $element_id
     * @return array
     */
    private function action_element_item(int $element_id): array {
        $element = element::load_by_id($element_id);
        $heading_name = format_string($element->title);
        $activity_id = $this->get_optional_param('back_to_activity', null, PARAM_INT);
        $element_identifier = $this->get_optional_param('back_to_reporting_ids', null, PARAM_TEXT);
        if ($activity_id) {
            $activity = activity::load_by_id($activity_id);
            $back_to = $this->get_back_to_activity(['activity_id' => $activity_id], format_string($activity->name));
        } else if ($element_identifier) {
            $back_to = $this->get_back_to_element_identifier(['element_identifier' => $element_identifier]);
        } else {
            $back_to = $this->get_back_to_by_content_tab();
        }
        return [$heading_name, $back_to];
    }

    /**
     * @param int $subject_instance_id
     * @return array
     */
    private function action_subject_instance_item(int $subject_instance_id): array {
        $subject_instance = subject_instance::load_by_id($subject_instance_id);
        $heading_name = format_string($subject_instance->get_activity()->name);
        $subject_user_id = $this->get_optional_param('back_to_subject_user', null, PARAM_INT);
        if ($subject_user_id) {
            $subject_user = \core_user::get_user($subject_user_id);
            $back_to = $this->get_back_to_subject_user(['subject_user_id' => $subject_user->id], fullname($subject_user));
        } else {
            $back_to = $this->get_back_to_user_tab();
        }
        return [$heading_name, $back_to];
    }
}