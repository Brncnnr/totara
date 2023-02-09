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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @package totara
 * @subpackage message
 */

/**
 * Upgrade code for the oauth plugin
 */

function xmldb_totara_message_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2021010500) {
        // Adding notification's id field and indexes to the table.
        $table = new xmldb_table('message_metadata');

        // Adding notification's id field
        $notification_id_field = new xmldb_field(
            'notificationid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'messagereadid'
        );

        $notification_id_field->setComment("The table notification's id");

        if (!$dbman->field_exists($table, $notification_id_field)) {
            $dbman->add_field($table, $notification_id_field);
        }

        // Adding time read field
        $time_read_field = new xmldb_field('timeread', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'notificationid');
        $time_read_field->setComment("The time read where this message notification is set to be dismissed");

        if (!$dbman->field_exists($table, $time_read_field)) {
            $dbman->add_field($table, $time_read_field);

            // First ensure we don't have any records with a null notificationid as MSSQL doesn't allow unique indexes with more than 1 NULL value
            // Using the id as it will be unique
            $DB->execute("UPDATE {message_metadata} SET notificationid = -1 * id WHERE notificationid IS NULL");
        }

        // Add index for notification's id field.
        $notification_index = new xmldb_index('unique_notification_id', XMLDB_INDEX_UNIQUE, ['notificationid', 'processorid']);
        if (!$dbman->index_exists($table, $notification_index)) {
            $dbman->add_index($table, $notification_index);
        }

        upgrade_plugin_savepoint(true, 2021010500, 'totara', 'message');
    }

    if ($oldversion < 2021010501) {
        $table = new xmldb_table('message_metadata');

        // Define index message (not unique) to be dropped form message_metadata.
        $message_index = new xmldb_index('message', XMLDB_INDEX_NOTUNIQUE, array('messageid'));

        // Conditionally launch drop index message.
        if ($dbman->index_exists($table, $message_index)) {
            $dbman->drop_index($table, $message_index);
        }

        // Define index messageread (not unique) to be dropped form message_metadata.
        $message_read_index = new xmldb_index('messageread', XMLDB_INDEX_NOTUNIQUE, array('messagereadid'));

        // Conditionally launch drop index messageread.
        if ($dbman->index_exists($table, $message_read_index)) {
            $dbman->drop_index($table, $message_read_index);
        }

        // Message savepoint reached.
        upgrade_plugin_savepoint(true, 2021010501, 'totara', 'message');
    }

    return true;
}
