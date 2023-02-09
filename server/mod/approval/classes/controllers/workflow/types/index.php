<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\workflow\types;

use mod_approval\views\embedded_report_view;
use mod_approval\views\override_nav_breadcrumbs;
use mod_approval\output\workflow_type_report_add_button as add_button;
use totara_mvc\has_report;

/**
 * Class index, 'Manage workflow type' report page
 */
class index extends base {

    use has_report;

    public const URL = '/mod/approval/workflow/types/index.php';

    /**
     * @inheritDoc
     */
    public function action() {

        $this->can_manage_workflows();

        $this->set_url(static::get_url());

        $report = $this->load_embedded_report('approval_workflow_type', [], true);
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);

        $heading = get_string('manage_approval_workflows_types', 'mod_approval');

        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_approval/workflow_type_report_embedded')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($heading);
        $report_view->set_additional_data(
            [
                'add_button' => $report_view->get_renderer()->render(add_button::create())
            ]
        );
        return $report_view;
    }
}