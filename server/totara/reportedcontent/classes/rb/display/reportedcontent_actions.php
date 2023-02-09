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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\rb\display;

use totara_reportbuilder\rb\display\base;
use totara_reportedcontent\review;

/**
 * Actions for tenants.
 */
final class reportedcontent_actions extends base {
    /**
     * Render out the action buttons for the report
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     * @throws \Exception
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report): string {
        global $OUTPUT;

        $extra_fields = static::get_extrafields_row($row, $column);
        $status = (int) $extra_fields->status;

        // Render some buttons for actions here, as long as it's a HTML page & the status is happy
        if ($format === 'html' && review::DECISION_PENDING === $status) {
            return $OUTPUT->render_from_template('totara_reportedcontent/report_actions', [
                'report_id' => $value,
            ]);
        }

        // Fall back to showing a label
        switch ($status) {
            case review::DECISION_REMOVE:
                return get_string('status_removed', 'rb_source_reportedcontent');
            case review::DECISION_APPROVE:
                return get_string('status_approved', 'rb_source_reportedcontent');
            default:
                return '';
        }
    }
}
