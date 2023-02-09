<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_xapi
 */
defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_totara_xapi_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021081800) {
        // Define table xapi_statement to be created.
        $table = new xmldb_table('xapi_statement');

        // Adding fields to table xapi_statement.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('statement', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table xapi_statement.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table xapi_statement.
        $table->add_index('component_idx', XMLDB_INDEX_NOTUNIQUE, ['component']);
        $table->add_index('time_created_idx', XMLDB_INDEX_NOTUNIQUE, ['time_created']);

        // Conditionally launch create table for xapi_statement.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Paypal savepoint reached.
        upgrade_plugin_savepoint(true, 2021081800, 'totara', 'xapi');
    }

    if ($oldversion < 2021090200) {
        // Define table totara_xapi_statement to be renamed to totara_xapi_statement.
        $table = new xmldb_table('xapi_statement');

        if ($dbman->table_exists("xapi_statement")) {
            // Launch rename table for totara_xapi_statement.
            $dbman->rename_table($table, 'totara_xapi_statement');
        }

        // Xapi savepoint reached.
        upgrade_plugin_savepoint(true, 2021090200, 'totara', 'xapi');
    }

    if ($oldversion < 2021092800) {
        // Define field user_id to be added to totara_xapi_statement.
        $table = new xmldb_table('totara_xapi_statement');
        $field = new xmldb_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'time_created');

        // Conditionally launch add field user_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index user_id_idx (not unique) to be added to totara_xapi_statement.
        $index = new xmldb_index('user_id_idx', XMLDB_INDEX_NOTUNIQUE, array('user_id'));

        // Conditionally launch add index user_id_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Xapi savepoint reached.
        upgrade_plugin_savepoint(true, 2021092800, 'totara', 'xapi');
    }


    if ($oldversion < 2021110801) {
        $table = new xmldb_table('totara_xapi_statement');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $index = new xmldb_index('component_idx', XMLDB_INDEX_NOTUNIQUE, array('component'));

        // Conditionally launch drop index component_idx.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Conditionally launch drop field component.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2021110801, 'totara', 'xapi');
    }

    if ($oldversion < 2022042701) {

        // Define field client_id to be added to totara_xapi_statement.
        $table = new xmldb_table('totara_xapi_statement');
        $field = new xmldb_field('client_id', XMLDB_TYPE_CHAR, '80', null, false, null, null, 'user_id');

        // Conditionally launch add field client_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Xapi savepoint reached.
        upgrade_plugin_savepoint(true, 2022042701, 'totara', 'xapi');
    }

    return true;
}