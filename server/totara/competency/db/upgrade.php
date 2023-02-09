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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

/**
 * Database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 */
function xmldb_totara_competency_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2021012600) {
        // Define field minproficiencyid to be added to totara_competency_assignments.
        $table = new xmldb_table('totara_competency_assignments');
        $field = new xmldb_field('minproficiencyid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'expand');

        // Conditionally launch add field minproficiencyid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key compscal_min_fk (foreign) to be added to totara_competency_assignments.
        $table = new xmldb_table('totara_competency_assignments');
        $key = new xmldb_key('compscal_min_fk', XMLDB_KEY_FOREIGN, array('minproficiencyid'), 'comp_scale_values', array('id'));

        // Launch add key compscal_min_fk.
        $dbman->add_key($table, $key);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2021012600, 'totara', 'competency');
    }

    if ($oldversion < 2021050500) {
        require_once $CFG->dirroot . '/totara/competency/db/upgradelib.php';

        totara_competency_upgrade_update_aggregation_method_setting();

        // Criteria savepoint reached.
        upgrade_plugin_savepoint(true, 2021050500, 'totara', 'competency');
    }

    if ($oldversion < 2021050501) {
        // Changing nullability of field date_achieved on table totara_competency_pathway_achievement to null.
        $table = new xmldb_table('totara_competency_pathway_achievement');
        $field = new xmldb_field('date_achieved', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'scale_value_id');

        // Launch change of nullability for field date_achieved.
        $dbman->change_field_notnull($table, $field);

        // Competency savepoint reached.
        upgrade_plugin_savepoint(true, 2021050501, 'totara', 'competency');
    }


    return true;
}
