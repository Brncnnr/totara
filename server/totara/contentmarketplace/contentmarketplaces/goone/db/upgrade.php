<?php
/*
 * This file is part of Totara LMS
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package contentmarketplace_goone
 */

defined('MOODLE_INTERNAL') || die;

/**
 * GO1 content marketplace plugin upgrade.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_contentmarketplace_goone_upgrade($oldversion) {
    global $CFG, $DB;
    require_once(__DIR__ . '/upgradelib.php');

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2021092300) {

        // Define table marketplace_goone_learning_object to be created.
        $table = new xmldb_table('marketplace_goone_learning_object');

        // Adding fields to table marketplace_goone_learning_object.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('external_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table marketplace_goone_learning_object.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table marketplace_goone_learning_object.
        $table->add_index('external_id_index', XMLDB_INDEX_UNIQUE, array('external_id'));

        // Conditionally launch create table for marketplace_goone_learning_object.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        contentmarketplace_goone_create_course_module_source_records();

        // Goone savepoint reached.
        upgrade_plugin_savepoint(true, 2021092300, 'contentmarketplace', 'goone');
    }

    return true;
}
