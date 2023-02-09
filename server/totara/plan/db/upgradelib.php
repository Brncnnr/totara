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
 * @package totara_plan
 */


/**
 * Create and fill table for record of learning
 */
function totara_plan_upgrade_record_of_learning() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table dp_record_of_learning to be created.
    $table = new xmldb_table('dp_record_of_learning');

    // Adding fields to table dp_record_of_learning.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table dp_record_of_learning.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('user_id_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

    // Adding indexes to table dp_record_of_learning.
    $table->add_index('rol_unique', XMLDB_INDEX_UNIQUE, array('userid', 'instanceid', 'type'));
    $table->add_index('instanceid', XMLDB_INDEX_NOTUNIQUE, array('instanceid'));

    // Conditionally launch create table for dp_record_of_learning.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    if (!$DB->record_exists('dp_record_of_learning', [])) {
        $insert_sql = "
                INSERT INTO {dp_record_of_learning} (userid, instanceid, type)
                SELECT ue.userid, e.courseid, 1
                FROM {user_enrolments} ue
                JOIN {enrol} e ON ue.enrolid = e.id
                JOIN {course} c ON e.courseid = c.id 
                    AND (c.containertype = 'container_course' OR c.containertype = 'container_site')
                UNION
                SELECT cc.userid, cc.course, 1
                FROM {course_completions} cc
                JOIN {course} c ON cc.course = c.id
                WHERE cc.status > 10
                UNION
                SELECT p1.userid, pca1.courseid, 1
                FROM {dp_plan_course_assign} pca1
                JOIN {dp_plan} p1 ON pca1.planid = p1.id
                JOIN {course} c ON pca1.courseid = c.id
            ";

        $DB->execute($insert_sql);
    }
}

