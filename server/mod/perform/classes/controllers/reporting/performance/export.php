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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\reporting\performance;

use coding_exception;
use context;
use core\output\notification;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\activity;
use mod_perform\util;
use moodle_exception;
use moodle_url;
use reportbuilder;
use totara_mvc\has_report;
use totara_mvc\view;

class export extends perform_controller {

    use has_report;

    public const SHORT_NAME_ELEMENT_IDENTIFIER = 'element_identifier';
    public const SHORT_NAME_ELEMENT = 'element';
    public const SHORT_NAME_SUBJECT_INSTANCE = 'subject_instance';

    // Only support CSV/Excel for now.
    // Could potentially use report::get_report_export_options() setting later if required.
    /** @var array ALLOWED_EXPORT  */
    public const ALLOWED_EXPORT = [
        'csv',
        'excel'
    ];

    /**
     * mod_perform\models\activity\activity instance
     * @var activity $activity
     */
    private $activity;

    public function setup_context(): context {
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
                get_string('bulk_export_no_params_warning_message', 'mod_perform', (object)['url' => $link_url->out(true)]),
                notification::NOTIFY_WARNING
            )
        ]);
    }

    public function action_item() {
        // Basic check for the right permissions. Per row filtering done in report query.
        if (!util::can_potentially_report_on_subjects($this->currently_logged_in_user()->id)) {
            throw new moodle_exception('error_export_permission_missing', 'mod_perform');
        }

        $activity_id = $this->get_optional_param('activity_id', null, PARAM_INT);
        $subject_user_id = $this->get_optional_param('subject_user_id', null, PARAM_INT);
        $subject_instance_id = $this->get_optional_param('subject_instance_id', null, PARAM_INT);
        $element_id = $this->get_optional_param('element_id', null, PARAM_INT);
        $export_format = $this->get_required_param('format', PARAM_ALPHA);

        $extra_data = compact('activity_id', 'subject_user_id', 'subject_instance_id', 'element_id');

        // This triggers the export because export and format params are set.
        $report = $this->load_embedded_report('perform_response_export', $extra_data, true, true);

        // Comment out the next line and uncomment the block below to output to screen instead of file for debugging.
        return $this->handle_export($report, $export_format);

        /*
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);
        $this->set_url(static::get_url($extra_data));
        return self::create_report_view($report, $debug);
        */
    }

    public function action_bulk() {
        global $DB;

        // Basic check for the right permissions. Per row filtering done in report query.
        if (!util::can_potentially_report_on_subjects($this->currently_logged_in_user()->id)) {
            throw new moodle_exception('error_export_permission_missing', 'mod_perform');
        }

        $export_format = $this->get_required_param('format', PARAM_ALPHA);
        $export_type = $this->get_required_param('filtered_report_export_type', PARAM_ALPHAEXT);
        $filtered_report_filter_hash = $this->get_required_param('filtered_report_filter_hash', PARAM_ALPHANUM);

        switch ($export_type) {
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
                throw new moodle_exception('bulk_export_type_incorrect', 'mod_perform');
        }

        // Pass prevent_export=true here to stop has_report trait exporting this report.
        $report = $this->load_embedded_report($source_shortname, [], true, true);

        $active_filter_hash = $report->get_search_hash();

        if ($filtered_report_filter_hash != $active_filter_hash) {
            $url = new moodle_url('/mod/perform/manage/activity/export.php');
            $this->set_url(static::get_url());
            return self::create_view('mod_perform/no_report', [
                'content' => view::core_renderer()->notification(
                    get_string('bulk_export_filter_changed_warning_message', 'mod_perform', (object)['url' => $url->out(true)]),
                    notification::NOTIFY_WARNING
                )
            ]);
        }

        [$sql, $params] = $report->build_query(false, true, false);

        $data = $DB->get_records_sql($sql, $params, 0, self::BULK_EXPORT_MAX_ROWS);
        $ids = array_map(function ($item) {
            return $item->id;
        }, $data);

        switch ($export_type) {
            case self::SHORT_NAME_ELEMENT:
                if (!empty($ids)) {
                    $extra_data['element_id'] = $ids;
                }
                $extra_data['activity_id'] = $this->get_required_param('activity_id_export_filter', PARAM_INT);
                break;
            case self::SHORT_NAME_SUBJECT_INSTANCE:
                if (!empty($ids)) {
                    $extra_data['subject_instance_id'] = $ids;
                }
                $extra_data['subject_user_id'] = $this->get_required_param('subject_user_id_export_filter', PARAM_INT);
                break;
            case self::SHORT_NAME_ELEMENT_IDENTIFIER:
                $identifiers = $this->get_required_param('element_identifier_export_filter', PARAM_TEXT);
                $identifiers = explode(',', $identifiers);
                foreach ($identifiers as $reporting_id) {
                    if (!is_number($reporting_id)) {
                        throw new coding_exception('Integer Reporting identifier IDs expected');
                    }
                }

                $extra_data['element_identifier'] = $identifiers;

                if (!empty($ids)) {
                    $extra_data['element_id'] = $ids;
                }
                break;
            default:
                throw new moodle_exception('bulk_export_shortname_incorrect', 'mod_perform');
        }

        $report = $this->load_embedded_report('perform_response_export', $extra_data, true, true);

        // Comment out the next line and uncomment the block below to output to screen instead of file for debugging.
        return $this->handle_export($report, $export_format);

        /*
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);
        $this->set_url(static::get_url());
        return self::create_report_view($report, $debug);
        */
    }

    /**
     * Method to deal with exporting a report. This includes checking the format is supported and
     * rendering an error if not.
     *
     * @param reportbuilder $report
     * @param string $format
     * @return view
     */
    protected function handle_export(reportbuilder $report, string $format) {
        $format = \core_text::strtolower($format);
        if (!in_array($format, self::ALLOWED_EXPORT)) {
            $this->set_url(static::get_url());
            return self::create_view('mod_perform/no_report', [
                'content' => view::core_renderer()->notification(
                    get_string('export_unsupported_format_warning_message', 'mod_perform'),
                    notification::NOTIFY_WARNING
                )
            ]);
        }

        $current_export_options = $report->get_report_export_options();
        $all_export_options = reportbuilder::get_all_general_export_options(true);
        $format_name = $all_export_options[$format] ?? $format;
        if (!in_array($format, array_keys($current_export_options))) {
            // Invalid format for this report - tell them to get admin to add to export options or override in source.
            $this->set_url(static::get_url());
            return self::create_view('mod_perform/no_report', [
                'content' => view::core_renderer()->notification(
                    get_string('export_invalid_format_warning_message', 'mod_perform', $format_name),
                    notification::NOTIFY_WARNING
                )
            ]);
        }

        // All okay, export the data.
        return $report->export_data($format, false);
    }

    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/export.php';
    }
}
