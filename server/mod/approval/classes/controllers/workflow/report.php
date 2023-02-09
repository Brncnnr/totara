<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\controllers\workflow;

use mod_approval\model\workflow\workflow;
use mod_approval\views\override_nav_breadcrumbs;
use totara_mvc\has_report;
use totara_mvc\report_view;

/**
 * Applications report controller
 */
class report extends base {
    use has_report;

    /**
     * Page url
     *
     * @var string
     */
    public const URL =  '/mod/approval/workflow/report.php';

    /**
     * @inheritDoc
     */
    public function action(): report_view {
        $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);
        $page_url = self::get_url(['workflow_id' => $workflow_id]);
        $this->set_url($page_url);
        $workflow = workflow::load_by_id($workflow_id);
        $params = [
            'workflow_id' => $workflow_id,
            'workflow' => $workflow,
        ];

        $report = $this->load_embedded_report('application_form_responses', $params);
        $page_title = get_string(
            'application_form_response_report_title',
            'mod_approval',
            (object) [
                'title' => $workflow->name
            ]
        );
        $report_view = (new report_view('totara_mvc/report', $report, $debug))
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($page_title);

        return $report_view;
    }
}
