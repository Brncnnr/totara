<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package block_totara_report_manager
 */

/**
 * Report manager block renderer
 */

namespace block_totara_report_manager\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Report manager block renderer
 *
 * @package block_totara_report_manager
 */
class renderer extends \plugin_renderer_base {

    /**
     * Renders html to reports list.
     *
     * @return string
     */
    public function report_list() {
        global $PAGE;

        // Prepare the data for the list of reports.
        $reports = get_my_reports_list();

        $defaultview = get_config('totara_reportbuilder', 'defaultreportview');
        $showdescription = get_config('totara_reportbuilder', 'showdescription');

        $renderer = $PAGE->get_renderer('totara_core');

        // Build the template data.
        $data = new \stdClass();
        $data->report_list = $renderer->report_list_export_for_template($reports, false);

        $data->canedit = false;
        $data->isgrid = $defaultview === 'grid';
        $data->islist = $defaultview === 'list';
        $data->showdescription = $showdescription;

        return $this->render_from_template('totara_core/myreports', $data);
    }
}
