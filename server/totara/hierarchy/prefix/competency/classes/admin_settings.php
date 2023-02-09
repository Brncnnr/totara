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
 * @package hierarchy_competency
 */

namespace hierarchy_competency;

defined('MOODLE_INTERNAL') || die();

use admin_root;
use admin_settingpage;
use lang_string;
use totara_core\advanced_feature;

class admin_settings {

    /**
     * The settings for competency contain settings from other plugins.
     * As we can only create the settings page in the admin tree once we make sure it's
     * either loaded or created here. Other plugins can use this method
     * to get the settings page and add their settings to it.
     *
     * @param admin_root $menu
     * @return admin_settingpage|null
     */
    public static function load_or_create_settings_page(admin_root $menu): admin_settingpage {
        // If it's not already there create it
        // Other competency related plugins can add their settings to that page
        if (!$settings_page = $menu->locate('hierarchy_competency_settings')) {
            $settings_page = new admin_settingpage(
                'hierarchy_competency_settings',
                new lang_string('settings', 'hierarchy_competency'),
                [],
                !advanced_feature::is_enabled('competencies')
            );

            $menu->add('competencies', $settings_page);
        }

        return $settings_page;
    }

}