<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use core\plugininfo\totara;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_achievement;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

class plugininfo extends totara {
    public function get_usage_for_registration_data() {
        $data = array();
        $data['numassignments'] = assignment::repository()->filter_by_not_draft()->count();
        $data['numuserassignments'] = competency_assignment_user::repository()->count();
        $data['numachievements'] = competency_achievement::repository()->count();
        $data['competencyassignmentsenabled'] = (int)advanced_feature::is_enabled('competency_assignment');

        return $data;
    }
}