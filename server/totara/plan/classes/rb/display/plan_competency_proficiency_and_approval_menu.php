<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_plan
 */

namespace totara_plan\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Displays the competency's proficiency/approval status, and if the current user would have permission
 * to change the competency's status via the competency page of the learning plan, it gives them
 * a drop-down menu to change the status, which saves changes via Javascript
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_plan
 *
 * @deprecated since Totara 13.0
 */
class plan_competency_proficiency_and_approval_menu extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG, $DB;

        debugging('plan_competency_proficiency_and_approval_menu class has been deprecated, please use format_string() instead.', DEBUG_DEVELOPER);

        // Needed for approval constants.
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        if ($isexport) {
          return $value;
        }

        $content = array();
        $approved = isset($extrafields->approved) ? $extrafields->approved : null;
        $planid = isset($extrafields->planid) ? $extrafields->planid : null;

        if (!$planid) {
            return $value;
        } else {
            if (!array_key_exists($planid, $report->src->dp_plans)) {
                $plan = new \development_plan($planid);
                $report->src->dp_plans[$planid] = $plan;
            }
            $content[] = $value;
        }

        // Highlight if the item has not yet been approved.
        if ($approved != DP_APPROVAL_APPROVED) {
            $itemstatus = \totara_plan\rb\display\plan_item_status::display($approved, $format, $extrafields, $column, $report);
            if ($itemstatus) {
                $content[] = $itemstatus;
            }
        }
        return implode(\html_writer::empty_tag('br'), $content);

    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
