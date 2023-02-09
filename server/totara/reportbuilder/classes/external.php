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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder;

use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use external_warnings;

global $CFG;
require_once("$CFG->libdir/externallib.php");

/**
 * External services for reportbuilder.
 */
final class external extends \external_api {

    /**
     * Returns description of set_default_search parameters.
     *
     * @return external_function_parameters
     */
    public static function set_default_search_parameters() {
        return new external_function_parameters([
            'reportid'    => new external_value(PARAM_INT, 'Report id'),
            'sid'         => new external_value(PARAM_INT, 'Saved search id'),
            'setdefault'  => new external_value(PARAM_BOOL, 'Set saved search as default'),
        ]);
    }

    /**
     * Set a users saved search as a default search
     *
     * @param int $reportid The report id
     * @param int $sid The report_builder_saved id
     * @param bool $setdefault True to set as default
     * @return array Containing the list of users saved searches
     */
    public static function set_default_search(int $reportid, int $sid, bool $setdefault) : array {
        global $CFG, $DB, $USER;

        self::validate_context(\context_system::instance());

        $warnings = [];

        require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

        // Check access.
        if (!isloggedin() || isguestuser()) {
            // No saving for guests.
            $warnings[] = [
                'warningcode' => 'nosavingforguests',
                'message' => 'No saving for guests.'
            ];

            return [
                'savedsearches' => [],
                'warnings' => $warnings
            ];
        }

        $params = self::validate_parameters(
            self::set_default_search_parameters(),
            ['reportid' => $reportid, 'sid' => $sid, 'setdefault' => $setdefault]
        );

        $reportid = $params['reportid'];
        $sid = $params['sid'];
        $setdefault = $params['setdefault'];

        // Make sure the report actually exists.
        $reportrecord = $DB->get_record('report_builder', ['id' => $reportid], '*', MUST_EXIST);

        // Check the user has permission to view the report.
        if (!\reportbuilder::is_capable($reportid)) {
            $warnings[] = [
                'warningcode' => 'nopermission',
                'message' => 'You do not have permission for this report.'
            ];

            return [
                'savedsearches' => [],
                'warnings' => $warnings
            ];
        }

        // Check the user has access to the report and saved search.
        $sql = "SELECT 1
                  FROM {report_builder_saved}
                 WHERE id = :id
                   AND reportid = :reportid
                   AND (userid = :userid OR ispublic = 1)";
        $params = ['id' => $sid, 'userid' => $USER->id, 'reportid' => $reportid];

        if (!$DB->record_exists_sql($sql, $params)) {
            $warnings[] = [
                'warningcode' => 'noaccess',
                'message' => 'You do not have access to this report.'
            ];

            return [
                'savedsearches' => [],
                'warnings' => $warnings
            ];
        }

        // Verify global restrictions.
        $globalrestrictionset = \rb_global_restriction_set::create_from_page_parameters($reportrecord);

        // Get the report object with access checks.
        $config = new \rb_config();
        $config->set_global_restriction_set($globalrestrictionset);
        $report = \reportbuilder::create($reportid, $config, true);

        // Set the default.
        $newdefault = $setdefault ? $sid : 0;
        $default = $DB->get_record('report_builder_saved_user_default', ['userid' => $USER->id, 'reportid' => $reportid]);
        if ($default) {
            $default->savedid = $newdefault;
            $DB->update_record('report_builder_saved_user_default', $default);
        } else {
            $data = new \stdClass();
            $data->userid = $USER->id;
            $data->reportid = $reportid;
            $data->savedid = $newdefault;
            $DB->insert_record('report_builder_saved_user_default', $data);
        }
        $report->set_user_default_search($newdefault);

        // Return the saved searches.
        $savedsearches = $report->get_saved_searches();
        $results = [];
        foreach ($savedsearches as $id => $name) {
            $result = [];
            $result['sid'] = $id;
            $result['name'] = $name;
            $results[] = $result;
        }

        $returndata = [
            'savedsearches' => $results,
            'warnings' => $warnings
        ];

        return $returndata;
    }

    /**
     * Returns description of set_default_search result values.
     *
     * @return external_single_structure
     */
    public static function set_default_search_returns() {
        return new external_single_structure(
            [
                'savedsearches'    => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'sid'  => new external_value(PARAM_INT,  'Saved search id'),
                            'name' => new external_value(PARAM_TEXT,  'Saved search name'),
                        ], 'Saved search'
                    ), 'list of saved searches'
                ),
                'warnings'  => new external_warnings()
            ]
        );
    }
}