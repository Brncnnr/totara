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
 * @package totara_comment
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_comment_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021051300) {

        // Define index parentid_idx (not unique) to be added to totara_comment.
        $table = new xmldb_table('totara_comment');
        $index = new xmldb_index('parentid_idx', XMLDB_INDEX_NOTUNIQUE, array('parentid'));

        // Conditionally launch add index parentid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index userid_idx (not unique) to be added to totara_comment.
        $index = new xmldb_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch add index parentid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Comment savepoint reached.
        upgrade_plugin_savepoint(true, 2021051300, 'totara', 'comment');
    }

    return true;
}
