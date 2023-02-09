<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_date_picker
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Database upgrade script
 *
 * @param integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return bool
 */
function xmldb_performelement_date_picker_upgrade($oldversion) {
    global $DB, $CFG;
    require_once $CFG->dirroot . '/mod/perform/element/date_picker/db/upgradelib.php';

    $dbman = $DB->get_manager();

    if ($oldversion < 2021092800) {
        performelement_date_picker_maintain_active_date_picker_year_ranges();

        upgrade_plugin_savepoint(true, 2021092800, 'performelement', 'date_picker');
    }

    return true;
}
