<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Rusell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage completionimport
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_completionimport_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2021012500) {
        // Define field processed to be added to totara_compl_import_course.
        $table = new xmldb_table('totara_compl_import_course');
        $field = new xmldb_field('processed', XMLDB_TYPE_INTEGER, '1', null, null, null, 0, 'completiondateparsed');

        // Conditionally launch add field processed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Update all existing records to be imported
            $DB->execute('UPDATE {totara_compl_import_course} SET processed = 1');
        }

        // Completionimport savepoint reached.
        upgrade_plugin_savepoint(true, 2021012500, 'totara', 'completionimport');
    }

    return true;
}
