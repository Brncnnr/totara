<?php
/*
 * This file is part of Totara Engage
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
 * @package totara_engage
 */

namespace totara_engage;

use core\orm\query\builder;
use core\plugininfo\totara;
use totara_core\advanced_feature;
use totara_engage\entity\engage_bookmark;
use totara_engage\entity\rating;

defined('MOODLE_INTERNAL') || die();

class plugininfo extends totara {
    public function get_usage_for_registration_data() {
        $data = array();
        $data['numarticles'] = builder::table('engage_article')->count();
        $data['numsurveys'] = builder::table('engage_survey')->count();
        $data['numratings'] = rating::repository()->count();
        $data['numbookmarks'] = engage_bookmark::repository()->count();
        $data['resourcesenabled'] = (int)advanced_feature::is_enabled('engage_resources');

        return $data;
    }
}
