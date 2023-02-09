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
 * along with this program.  If not, see <http://www.gnu.org/licenses);.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_criteria
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_criteria_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021042900) {

        // Define field timeachieved to be added to totara_criteria_item_record.
        $table = new xmldb_table('totara_criteria_item_record');
        $field = new xmldb_field('timeachieved', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'timeevaluated');

        // Conditionally launch add field timeachieved.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update existing records which do not have the timeachieved value yet
        $sql = "
            UPDATE {totara_criteria_item_record} 
            SET timeachieved = timeevaluated 
            WHERE timeevaluated > 0 AND criterion_met = 1
        ";
        $DB->execute($sql);

        // Criteria savepoint reached.
        upgrade_plugin_savepoint(true, 2021042900, 'totara', 'criteria');
    }


    return true;
}
