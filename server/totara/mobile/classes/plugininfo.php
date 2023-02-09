<?php
/*
 * This file is part of Totara Learn
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
 * @package totara_mobile
 */

namespace totara_mobile;

use core\orm\query\builder;
use core\plugininfo\totara;

defined('MOODLE_INTERNAL') || die();

class plugininfo extends totara {
    public function get_usage_for_registration_data() {
        $data = array();

        $data['mobileenabled'] = (int)get_config('totara_mobile', 'enable');
        $data['numdevices'] = builder::table('totara_mobile_devices')->count();
        $data['numcompatiblecourses'] = builder::table('totara_mobile_compatible_courses')->count();
        $data['numofflinescorms'] = builder::table('scorm')->where('allowmobileoffline', 1)->count();
        $data['airnotifierenabled'] = (int)!empty(get_config(null, 'totara_airnotifier_appcode'));
        $data['customairnotifier'] = (int)(get_config(null, 'totara_airnotifier_host') != 'https://push.totaralearning.com');

        return $data;
    }
}