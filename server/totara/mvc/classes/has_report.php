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

use rb_config;
use rb_global_restriction_set;
use reportbuilder;
use totara_reportbuilder\event\report_viewed;

/**
 * This trait can be used to easy handling of reports in a controller.
 *
 * Use `$report = $this->load_embedded_report('nameofreport');` to load the report
 *
 * @package totara_mvc
 */
trait has_report {

    /**
     * Load embedded report by it's shortname
     *
     * @param string $shortname shortname of the report
     * @param array|null $extra_data Optional extra embed data
     * @param bool $trigger_report_viewed_event by default the \totara_reportbuilder\event\report_viewed event being triggered, set to false to omit this behaviour
     * @param bool $prevent_export If true, report won't export even if 'format' param is set.
     * @return reportbuilder
     */
    protected function load_embedded_report(
        string $shortname,
        array $extra_data = [],
        bool $trigger_report_viewed_event = true,
        bool $prevent_export = false
    ): reportbuilder {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

        $sid = $this->get_optional_param('sid', 0, PARAM_INT);
        $report_record = $DB->get_record('report_builder', ['shortname' => $shortname]);
        $global_restriction_set = rb_global_restriction_set::create_from_page_parameters($report_record);
        $config = (new rb_config())
            ->set_embeddata($extra_data)
            ->set_sid($sid)
            ->set_global_restriction_set($global_restriction_set);

        $report = reportbuilder::create_embedded($shortname, $config);
        if (!$report) {
            print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
        }

        // Handle exporting the report
        $format = $this->get_optional_param('format', null, PARAM_ALPHANUMEXT);
        if (!$prevent_export && $format != '') {
            $report->export_data($format);
            die();
        }

        if ($trigger_report_viewed_event) {
            report_viewed::create_from_report($report)->trigger();
        }

        return $report;
    }

}