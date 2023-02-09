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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package performelement_multi_choice_multi
 */


/**
 * Database upgrade script
 *
 * @param integer $oldversion Current (pre-upgrade) local db version timestamp
 *
 * @return bool
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_performelement_multi_choice_multi_upgrade($oldversion) {
    require_once(__DIR__ . '/upgradelib.php');

    if ($oldversion < 2020110900) {
        performelement_multi_choice_multi_change_data();

        upgrade_plugin_savepoint(true, 2020110900, 'performelement', 'multi_choice_multi');
    }

    return true;
}