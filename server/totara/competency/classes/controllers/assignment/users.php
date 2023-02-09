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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\controllers\assignment;

use context_system;
use moodle_url;
use totara_competency\views\users as users_report_view;
use totara_mvc\has_report;

class users extends base {

    use has_report;

    protected $admin_external_page_name = 'competency_assignment_users';

    public function action() {
        if (!has_capability('totara/competency:view_assignments', context_system::instance())) {
            require_capability('totara/competency:manage_assignments', context_system::instance());
        }
        require_capability('moodle/user:viewdetails', context_system::instance());

        $debug = $this->get_optional_param('debug', false, PARAM_BOOL);

        $report = $this->load_embedded_report('competency_assignment_users');

        return users_report_view::create_from_report($report, $debug)
            ->set_back_to(
                new moodle_url('/totara/competency/assignments/index.php'),
                get_string('assignment_back_to_assignments', 'totara_competency')
            );
    }

}
