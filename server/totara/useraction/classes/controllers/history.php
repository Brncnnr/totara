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

namespace totara_useraction\controllers;

use moodle_url;
use totara_mvc\has_report;
use totara_mvc\report_view;
use totara_useraction\model\scheduled_rule;

/**
 * Base action for listing the scheduled actions.
 */
class history extends base_scheduled_action {
    use has_report;

    /**
     * @return report_view
     */
    public function action(): report_view {

        $title = get_string('history_title_all', 'totara_useraction');
        $page_url = new moodle_url('/totara/useraction/history.php');
        $this->require_capability('totara/useraction:manage_actions', $this->get_context());

        if ($this->get_optional_param('rule_id', null, PARAM_INT)) {
            // Load the actual rule
            $scheduled_rule = $this->get_rule('rule_id');
            $title = format_string($scheduled_rule->name);
            $title = get_string('history_title', 'totara_useraction', $title);
            $page_url = new moodle_url('/totara/useraction/history.php?rule_id=' . $scheduled_rule->get_id());
        }

        $report = $this->load_embedded_report('useraction_history');
        $view = report_view::create_from_report($report);
        $this->set_url($page_url);
        $view->set_title($title);

        return $view;
    }
}
