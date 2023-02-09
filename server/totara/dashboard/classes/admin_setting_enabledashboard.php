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
 * @package totara_dashboard
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Totara enable dashboard admin setting
 *
 * @since 13.0
 */

class totara_dashboard_admin_setting_enabledashboard extends \totara_core_admin_setting_feature {

    /**
     * Save a setting
     * @param string $data
     * @return string empty of error string
     */
    public function write_setting($data) {
        if ((int)$data == \totara_core\advanced_feature::DISABLED && get_config('core', 'defaulthomepage') == HOMEPAGE_TOTARA_DASHBOARD) {
            set_config('defaulthomepage', HOMEPAGE_SITE);
        }
        return parent::write_setting($data);
    }
}