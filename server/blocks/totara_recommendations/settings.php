<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package block_totara_recommendations
 */

defined('MOODLE_INTERNAL') || die;

use \block_totara_recommendations\settings_helper;

if ($ADMIN->fulltree) {
    // Number of days worth of activity to take into account for trending.
    $settings->add(
        new admin_setting_configselect(
            'block_totara_recommendations_dayctr',
            new lang_string('dayctrlbl','block_totara_recommendations'),
            new lang_string('dayctrdesc', 'block_totara_recommendations'),
            1,
            settings_helper::get_counter(1, 14, 1)
        )
    );

    // Number of items to show in block.
    $settings->add(
        new admin_setting_configselect(
            'block_totara_recommendations_recctr',
            new lang_string('recctrlbl','block_totara_recommendations'),
            new lang_string('recctrdesc', 'block_totara_recommendations'),
            3,
            settings_helper::get_counter(3, 20, 1)
        )
    );

    // Number of items to over-fetch.
    $settings->add(
        new admin_setting_configselect(
            'block_totara_recommendations_overctr',
            new lang_string('overctrlbl','block_totara_recommendations'),
            new lang_string('overctrdesc', 'block_totara_recommendations'),
            1000,
            settings_helper::get_counter(100, 2000, 100)
        )
    );
}
