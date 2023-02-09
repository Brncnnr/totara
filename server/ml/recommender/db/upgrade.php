<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/ml/recommender/db/upgradelib.php');

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_ml_recommender_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020121100) {
        // Add field to indicate whether a particular recommendation has been seen by user.
        $table_name = 'ml_recommender_users';
        if ($dbman->table_exists($table_name)) {
            $table = new xmldb_table($table_name);
            $field = new xmldb_field('seen', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, "0", 'score');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $index = new xmldb_index('user_seen_score_idx', XMLDB_INDEX_NOTUNIQUE, ['user_id', 'seen', 'score']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        upgrade_plugin_savepoint(true, 2020121100, 'ml', 'recommender');
    }

    if ($oldversion < 2021021000) {
        ml_recommender_upgrade_default_configuration();

        // Update ml recommender savepoint.
        upgrade_plugin_savepoint(true, 2021021000, 'ml', 'recommender');
    }

    return true;
}