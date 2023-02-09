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

namespace totara_competency\views;

use moodle_url;
use reportbuilder;
use totara_mvc\report_view;

class users extends report_view {

    protected $title = ['title_users', 'totara_competency'];

    protected function prepare_output($report) {
        return array_merge(
            parent::prepare_output($report),
            [
                'title' => $this->title,
            ]
        );
    }

    protected function set_button(reportbuilder $report) {
        $edit_button = $report->edit_button();
        $sync_button = '';
        if (has_capability('totara/competency:manage_assignments', \context_system::instance())) {
            $sync_button = $this->get_renderer()->single_button(
                new moodle_url('/totara/competency/assignments/sync.php'),
                get_string('button_sync_users', 'totara_competency'),
                'post'
            );
        }

        $this->get_page()->set_button($sync_button.$edit_button);
    }

}
