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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

/**
 * Database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 */
function xmldb_totara_hierarchy_upgrade($oldversion) {
    global $CFG, $DB;
    require_once("{$CFG->dirroot}/totara/hierarchy/db/upgradelib.php");

    $dbman = $DB->get_manager();

    if ($oldversion < 2021072600) {
        // Define table goal_perform_status to be created.
        $table = new xmldb_table('goal_perform_status');

        // Adding fields to table goal_perform_status.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('goal_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('goal_personal_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('scale_value_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('activity_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('subject_instance_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status_changer_user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status_changer_relationship_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_perform_status.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));
        $table->add_key('fk_goal_id', XMLDB_KEY_FOREIGN, array('goal_id'), 'goal', array('id'), 'cascade');
        $table->add_key('fk_goal_personal_id', XMLDB_KEY_FOREIGN, array('goal_personal_id'), 'goal_personal', array('id'), 'cascade');
        $table->add_key('fk_scale_value_id', XMLDB_KEY_FOREIGN, array('scale_value_id'), 'goal_scale_values', array('id'));
        $table->add_key('fk_activity_id', XMLDB_KEY_FOREIGN, array('activity_id'), 'perform', array('id'), 'setnull');
        $table->add_key('fk_subject_instance_id', XMLDB_KEY_FOREIGN, array('subject_instance_id'), 'perform_subject_instance', array('id'), 'setnull');
        $table->add_key('fk_status_changer_user_id', XMLDB_KEY_FOREIGN, array('status_changer_user_id'), 'user', array('id'));
        $table->add_key('fk_status_changer_relationship_id', XMLDB_KEY_FOREIGN, array('status_changer_relationship_id'), 'totara_core_relationship', array('id'), 'cascade');

        // Conditionally launch create table for goal_perform_status.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2021072600, 'totara', 'hierarchy');
    }

    if ($oldversion < 2021090900) {
        $table = new xmldb_table('comp');
        $proficiency_expected = new xmldb_field('proficiencyexpected', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, 1);
        $evidence_count = new xmldb_field('evidencecount', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, 0);

        if ($dbman->field_exists($table, $proficiency_expected)) {
            $dbman->change_field_default($table, $proficiency_expected);
        }

        if ($dbman->field_exists($table, $evidence_count)) {
            $dbman->change_field_default($table, $evidence_count);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2021090900, 'totara', 'hierarchy');
    }

    if ($oldversion < 2021092000) {

        // Define table goal_item_target_date_history to be created.
        $table = new xmldb_table('goal_item_target_date_history');

        // Adding fields to table goal_item_target_date_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scope', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetdate', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_item_target_date_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table goal_item_target_date_history.
        $table->add_index('itemscope', XMLDB_INDEX_NOTUNIQUE, array('scope', 'itemid'));

        // Conditionally launch create table for goal_item_target_date_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2021092000, 'totara', 'hierarchy');
    }

    if ($oldversion < 2021092200) {
        // Initial population of table goal_item_target_date_history.
        totara_hierarchy_upgrade_init_goal_target_date_history();

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2021092200, 'totara', 'hierarchy');
    }

    if ($oldversion < 2022090600) {
        $table = new xmldb_table('comp');
        $field = new xmldb_field('copy_op_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('copy_op_idx', XMLDB_INDEX_NOTUNIQUE, array('copy_op_id'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2022090600, 'totara', 'hierarchy');
    }

    if ($oldversion < 2022101700) {
        $field = new xmldb_field('targetdate', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, 0);

        $tables = ['goal', 'goal_personal'];
        foreach ($tables as $table_name) {
            $DB->set_field($table_name, 'targetdate', 0, ['targetdate' => null]);

            $table = new xmldb_table($table_name);
            $dbman->change_field_notnull($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022101700, 'totara', 'hierarchy');
    }

    return true;
}
