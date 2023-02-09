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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package mod_contentmarketplace
 */

use core\orm\query\builder;

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_contentmarketplace_upgrade($oldversion) {
    $DB = builder::get_db();
    $dbman = $DB->get_manager();

    if ($oldversion < 2021072005) {

        // Define field intro to be added to contentmarketplace.
        $table = new xmldb_table('contentmarketplace');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        // Conditionally launch add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'intro');
        // Conditionally launch add field intro_format.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $records = $DB->get_records('contentmarketplace');
        foreach ($records as $record) {
            $object = $DB->get_record('marketplace_linkedin_learning_object', ['id' => $record->learning_object_id]);
            $record->intro = $object->description_include_html;
            $record->introformat = FORMAT_HTML;
            $result = $DB->update_record('contentmarketplace', $record);

            if (!$result) {
                throw new coding_exception('Updated not completed');
            }
        }

        // Contentmarketplace savepoint reached.
        upgrade_mod_savepoint(true, 2021072005, 'contentmarketplace');
    }
    return true;
}
