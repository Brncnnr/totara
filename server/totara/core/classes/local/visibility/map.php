<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\local\visibility;

use totara_core\visibility_controller;

defined('MOODLE_INTERNAL') || die();

use core\dml\sql;

/**
 * Map class
 *
 * This is designed to simplify working with a visibility map.
 *
 * These maps pre-resolver which roles have the view hidden capability for each item.
 * Items are courses, programs and certifications.
 * This allows visibility resolution in SQL in a more performant way, by utilising the pre-resolved map.
 *
 * @internal
 */
abstract class map {

    /**
     * Returns the map table name.
     *
     * @return string
     */
    abstract protected function get_map_table_name(): string;

    /**
     * Returns the instance id field name.
     *
     * e.g. courseid, programid
     *
     * @return string
     */
    abstract protected function get_instance_field_name(): string;

    /**
     * Returns the view hidden capability for the items within this map.
     *
     * @return string
     */
    abstract public function get_view_hidden_capability(): string;

    /**
     * Returns the context level for this map.
     *
     * @return int
     */
    abstract protected function get_context_level(): int;

    /**
     * Constructor
     */
    final public function __construct() {
        // This is just to make sure no one gets any funny ideas.
    }

    /**
     * Recalculates the complete map.
     *
     * @return bool
     */
    final public function recalculate_complete_map() {
        $table = $this->create_temp_table();
        if (!$table) {
            // This only happens when a recalculation is in progress and debugging has already been thrown.
            return false;
        }

        $this->calculate_map();
        $this->replace_map_contents();
        $this->drop_temp_table($table);
    }

    /**
     * Recalculates just the map entries for one item.
     *
     * @param int $instanceid
     * @return bool
     */
    final public function recalculate_map_for_instance(int $instanceid) {
        $table = $this->create_temp_table();
        if (!$table) {
            // This only happens when a recalculation is in progress and debugging has already been thrown.
            return false;
        }
        $this->calculate_map($instanceid);
        $this->delete_from_map($instanceid);
        $this->copy_map_contents();
        $this->drop_temp_table($table);
    }

    /**
     * Recalculates just the map entries for one role.
     *
     * @param int $roleid
     * @return bool
     */
    final public function recalculate_map_for_role(int $roleid) {
        $table = $this->create_temp_table();
        if (!$table) {
            // This only happens when a recalculation is in progress and debugging has already been thrown.
            return false;
        }
        $this->calculate_map(null, $roleid);
        $this->delete_from_map(null, $roleid);
        $this->copy_map_contents();
        $this->drop_temp_table($table);
    }

    /**
     * Returns a temp table definition that matches the map table definition.
     *
     * @return \xmldb_table
     */
    private function get_temp_table_definition(): \xmldb_table {
        $table = new \xmldb_table($this->get_temp_table_name());
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field($this->get_instance_field_name(), XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        return $table;
    }

    /**
     * Returns the temp table name.
     *
     * @return string
     */
    protected function get_temp_table_name(): string {
        return $this->get_map_table_name() . '_temp';
    }

    /**
     * Creates the temp table for this map.
     *
     * @return bool|\xmldb_table
     */
    private function create_temp_table() {
        global $DB;
        $manager = $DB->get_manager();
        $table = $this->get_temp_table_definition();
        if ($manager->table_exists($table)) {
            debugging('Recalculation already in progress.');
            return false;
        }
        $manager->create_temp_table($table);
        return $table;
    }

    /**
     * Drops the temp table.
     *
     * @param \xmldb_table $table
     */
    private function drop_temp_table(\xmldb_table $table) {
        global $DB;
        $manager = $DB->get_manager();
        if ($manager->table_exists($table)) {
            $manager->drop_table($table);
        }
    }

    /**
     * Deletes all of the entries from the map table that match the given instance and/or role.
     *
     * @param int|null $instanceid
     * @param int|null $roleid
     */
    protected function delete_from_map(int $instanceid = null, int $roleid = null) {
        global $DB;

        if (is_null($instanceid) && is_null($roleid)) {
            $DB->execute('TRUNCATE TABLE {' . $this->get_map_table_name() . '}');
            return;
        }
        $conditions = [];
        if (!is_null($instanceid)) {
            $conditions[$this->get_instance_field_name()] = $instanceid;
        }
        if (!is_null($roleid)) {
            $conditions['roleid'] = $roleid;
        }
        $DB->delete_records($this->get_map_table_name(), $conditions);
    }

    /**
     * Replaces the map table contents with the contents in the temp table.
     */
    private function replace_map_contents() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $this->delete_from_map();
        $this->copy_map_contents();
        $transaction->allow_commit();
        // Update the stats on the map table as it helps with performance now.
        \totara_core\access::analyze_table($this->get_map_table_name());
    }

    /**
     * Copies the contents of the temp table to the map table.
     */
    protected function copy_map_contents() {
        global $DB;
        $field = $this->get_instance_field_name();
        $table_map = $this->get_map_table_name();
        $table_temp = $this->get_temp_table_name();
        $sql = "INSERT INTO {{$table_map}} ({$field}, roleid) 
                SELECT {$field}, roleid FROM {{$table_temp}}";
        $DB->execute($sql);
    }

    /**
     * Calculates the map entries required for the items.
     *
     * If an instanceid or roleid are provided the entries calculated are limited to those.
     *
     * @param int|null $instanceid
     * @param int|null $roleid
     */
    private function calculate_map(int $instanceid = null, int $roleid = null) {
        global $DB;
        $table = $this->get_temp_table_name();
        $field = $this->get_instance_field_name();
        $level = $this->get_context_level();
        $params = [
            'capability' => $this->get_view_hidden_capability(),
            'level' => $level,
        ];
        $wheres = [];
        if ($instanceid !== null) {
            $wheres[] = "x.instanceid = :instanceid";
            $params['instanceid'] = $instanceid;
        }
        if ($roleid !== null) {
            $wheres[] = 'x.roleid = :roleid';
            $params['roleid'] = $roleid;
        }
        if (empty($wheres)) {
            $wheres[] = '1=1';
        }
        $where = join(' AND ', $wheres);

        $sql = "INSERT INTO {{$table}} ({$field}, roleid)
                SELECT instanceid, roleid FROM (
                     SELECT x.instanceid, x.roleid, CASE WHEN SUM(x.permission) > 0 THEN 1 ELSE 0 END AS permission
                     FROM (
                          SELECT ctx.instanceid, rc.roleid, rc.permission
                          FROM {context} ctx
                          JOIN {context_map} cm ON cm.childid = ctx.id
                          LEFT JOIN (
                              SELECT rc.contextid, rc.roleid, POWER(2, c.depth) * (rc.permission * ABS(rc.permission)) AS permission
                              FROM {role_capabilities} rc
                              JOIN {context} c ON c.id = rc.contextid
                              WHERE capability = :capability
                          ) rc ON rc.contextid = cm.parentid
                          WHERE ctx.contextlevel = :level AND roleid IS NOT NULL
                     ) x
                     WHERE {$where}
                     GROUP BY x.roleid, x.instanceid
                ) y
                WHERE permission > 0
                ORDER BY instanceid ASC, roleid ASC";
        $DB->execute($sql, $params);
        // Update stats on the temp table as it helps with the moving of data that comes next.
        \totara_core\access::analyze_table($this->get_temp_table_name());
    }

    /**
     * Returns an SQL snippet that can be used to get a count of all the roles that user is assigned to
     * that hold the view hidden cap, per item in the map table.
     *
     * @param int $userid
     * @param \context_user|false $usercontext
     * @return sql
     */
    final public function sql_view_hidden_roles(int $userid, $usercontext): sql {
        global $CFG;

        $table_map = $this->get_map_table_name();
        $field = $this->get_instance_field_name();
        $level = $this->get_context_level();

        $roleassignments = \totara_core\access::get_role_assignments_subquery($userid);

        $param_level = \moodle_database::get_unique_param('level');
        $sql = new sql(
            "SELECT map.{$field} AS id, COUNT(map.roleid) AS roles
                 FROM {{$table_map}} map
                 JOIN (
                     SELECT DISTINCT vh_ctx.instanceid AS id, vh_ra.roleid
                       FROM {context} vh_ctx
                       JOIN {context_map} vh_cm ON vh_cm.childid = vh_ctx.id
                       JOIN (
                           {$roleassignments}
                       ) vh_ra ON vh_ra.contextid = vh_cm.parentid
                      WHERE vh_ctx.contextlevel = :{$param_level}",
            [$param_level => $level]
        );

        // Mix in multitenancy snippet here if required.
        if (!empty($usercontext)) {
            $sql = $sql->append(base::tenant_id_sql($usercontext, 'vh_ctx'), ' AND ');
        }

        return $sql->append(" ) vh_x ON vh_x.id = map.{$field} AND vh_x.roleid = map.roleid GROUP BY map.{$field}", '');
    }

    /**
     * Returns an array of all map.
     *
     * This is a convenience method for {@see visibility_controller::get_all_maps()}
     *
     * @return map[]
     */
    final public static function all(): array {
        return visibility_controller::get_all_maps();
    }

    /**
     * Returns an array of all view hidden capabilities.
     *
     * @return string[]
     */
    final public static function view_hidden_capabilities(): array {
        return array_map(
            function (map $map) {
                return $map->get_view_hidden_capability();
            },
            self::all()
        );
    }
}