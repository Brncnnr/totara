<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

/**
 * Totara catalog type admin setting
 *
 * @since 13.0
 */
class totara_catalog_admin_setting_catalogtype extends admin_setting_configselect {

    /**
     * Save a setting
     * @param string $data
     * @return string empty of error string
     */
    public function write_setting($data) {
        if ($data != 'totara' && get_config('core', 'defaulthomepage') == HOMEPAGE_TOTARA_GRID_CATALOG) {
            $value = \totara_core\advanced_feature::is_enabled('totaradashboard') ? HOMEPAGE_TOTARA_DASHBOARD : HOMEPAGE_SITE;
            set_config('defaulthomepage', $value);
        }
        return parent::write_setting($data);
    }
}