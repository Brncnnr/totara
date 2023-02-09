<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\role_map;

use core\orm\query\builder;
use totara_core\local\visibility\map as core_map;

defined('MOODLE_INTERNAL') || die();

/**
 * Role-has-capability mapping class.
 */
abstract class role_map_base extends core_map {

    /**
     * Returns the capability id for the items within this map.
     *
     * @return int
     */
    public function get_view_hidden_capability_id(): string {
        $capability_name = $this->get_view_hidden_capability();
        $capability = builder::table('capabilities')
            ->where('name', '=', $capability_name)
            ->one(true);
        return $capability->id;
    }

    /**
     * Returns the map table name.
     *
     * @return string
     */
    public function get_map_table_name(): string {
        return 'approval_role_capability_map';
    }

    /**
     * Returns the instance id field name.
     *
     * e.g. courseid, programid
     *
     * @return string
     */
    protected function get_instance_field_name(): string {
        return 'instanceid';
    }

    /**
     * Returns the context level for this map.
     *
     * @return int
     */
    protected function get_context_level(): int {
        return CONTEXT_MODULE;
    }

    /**
     * Deletes all of the entries from the map table that match the given instance and/or role.
     *
     * @param int|null $instanceid
     * @param int|null $roleid
     */
    protected function delete_from_map(int $instanceid = null, int $roleid = null) {
        global $DB;

        $capability_id = $this->get_view_hidden_capability_id();
        $conditions = ['capabilityid' => $capability_id];

        if (!is_null($instanceid)) {
            $conditions[$this->get_instance_field_name()] = $instanceid;
        }
        if (!is_null($roleid)) {
            $conditions['roleid'] = $roleid;
        }
        $DB->delete_records($this->get_map_table_name(), $conditions);
    }

    /**
     * Copies the contents of the temp table to the map table.
     */
    protected function copy_map_contents() {
        global $DB;
        $field = $this->get_instance_field_name();
        $table_map = $this->get_map_table_name();
        $table_temp = $this->get_temp_table_name();
        $context_level = $this->get_context_level();
        $capability_id = $this->get_view_hidden_capability_id();
        $sql = "INSERT INTO {{$table_map}} ({$field}, contextlevel, roleid, capabilityid) 
                SELECT {$field}, {$context_level}, roleid, {$capability_id} FROM {{$table_temp}}";
        $DB->execute($sql);
    }

    /**
     * Supplies the conditions for joining the role map into a capability map query.
     *
     * @param builder $builder
     * @param string $instance_table_alias
     */
    public function get_assigned_capability_sql(builder $builder, string $instance_table_alias) {
        $builder->where_field('instanceid', '=', $instance_table_alias . '.id')
            ->where('contextlevel', '=', $this->get_context_level())
            ->where('capabilityid', '=', $this->get_view_hidden_capability_id());
    }
}