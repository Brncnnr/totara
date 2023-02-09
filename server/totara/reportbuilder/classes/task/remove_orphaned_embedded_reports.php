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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\task;

use core\orm\query\builder;
use core\task\scheduled_task;

/**
 * If a class for a report source has been removed, then we need to remove all embedded reports using that source.
 *
 * @package totara_reportbuilder\task
 */
class remove_orphaned_embedded_reports extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('remove_orphaned_embedded_reports', 'totara_reportbuilder');
    }

    /**
     * Process orphaned embedded reports
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        $sources = array_keys(\reportbuilder::get_source_list(true, true));

        $orphaned_reports = builder::table('report_builder')
            ->select('id')
            ->where('embedded', 1)
            ->where_not_in('source', $sources);

        $orphaned_reports_count = $orphaned_reports->count();
        if (!$orphaned_reports_count) {
            return;
        }

        foreach ($orphaned_reports->get_lazy() as $report) {
            reportbuilder_delete_report($report->id);
        }
        mtrace("Removed {$orphaned_reports_count} orphaned embedded reports.");
    }

}
