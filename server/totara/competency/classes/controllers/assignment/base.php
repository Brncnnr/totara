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
 * @package totara_userstatus
 */

namespace totara_competency\controllers\assignment;

use context;
use context_system;
use totara_core\advanced_feature;
use totara_mvc\admin_controller;

class base extends admin_controller {

    protected $layout = 'noblocks';

    /**
     * Override get_default_context() either returning a system or a specific context.
     * You can also call $this->set_context() in your action later
     *
     * @return context
     */
    protected function setup_context(): context {
        advanced_feature::require('competency_assignment');

        $this->require_capability('totara/competency:manage_assignments', context_system::instance());
        $this->require_capability('moodle/user:viewdetails', context_system::instance());

        return \context_system::instance();
    }
}