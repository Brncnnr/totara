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
 * @package mod_perform
 */

namespace mod_perform;

use core\plugininfo\mod;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\element_response;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

class plugininfo extends mod {
    public function get_usage_for_registration_data() {
        $data = array();
        $data['numactivities'] = activity::repository()->filter_by_not_draft()->count();
        $data['numuserassignments'] = track_user_assignment::repository()->filter_by_active()->count();
        $data['numsubjectinstances'] = subject_instance::repository()->count();
        $data['numparticipantinstances'] = participant_instance::repository()->count();
        $data['numelementresponses'] = element_response::repository()->count();
        $data['performanceactivitiesenabled'] = (int)advanced_feature::is_enabled('performance_activities');

        return $data;
    }
}
