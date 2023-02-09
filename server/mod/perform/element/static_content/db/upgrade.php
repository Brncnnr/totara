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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_static_content
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Database upgrade script
 *
 * @param integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return bool
 */
function xmldb_performelement_static_content_upgrade($oldversion) {
    global $DB, $CFG;
    require_once $CFG->dirroot . '/mod/perform/element/static_content/db/upgradelib.php';

    $dbman = $DB->get_manager();

    if ($oldversion < 2020111700) {
        performelement_static_content_fix_broken_elements();

        // Perform savepoint reached.
        upgrade_plugin_savepoint(true, 2020111700, 'performelement', 'static_content');
    }

    return true;
}
