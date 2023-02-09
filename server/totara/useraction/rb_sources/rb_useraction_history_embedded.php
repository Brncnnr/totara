<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

/**
 * Embedded report for the useraction history table.
 */
class rb_useraction_history_embedded extends rb_base_embedded {
    /**
     * @throws coding_exception
     */
    public function __construct() {
        $this->url = '/totara/useraction/history.php';
        $this->source = 'useraction_history';
        $this->shortname = 'useraction_history';
        $this->fullname = get_string('sourcetitle', 'rb_source_useraction_history');
        $this->columns = [
            ['type' => 'useraction_history', 'value' => 'created', 'heading' => null],
            ['type' => 'useraction_history', 'value' => 'scheduled_rule', 'heading' => null],
            ['type' => 'user', 'value' => 'namelink', 'heading' => null],
            ['type' => 'useraction_history', 'value' => 'action', 'heading' => null],
            ['type' => 'useraction_history', 'value' => 'success', 'heading' => null],
            ['type' => 'useraction_history', 'value' => 'message', 'heading' => null],
        ];

        $this->filters = [
            ['type' => 'useraction_history', 'value' => 'success', 'advanced' => 1],
            [
                'type' => 'useraction_history',
                'value' => 'created',
                'advanced' => 1,
            ],
        ];

        $this->defaultsortcolumn = 'useraction_history_id';
        $this->defaultsortorder = SORT_DESC;

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function embedded_global_restrictions_supported(): bool {
        return false;
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $reportfor id of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return bool true if the user can access this report
     */
    public function is_capable($reportfor, $report): bool {
        $context = context_system::instance();
        return has_capability('totara/useraction:manage_actions', $context, $reportfor);
    }
}
