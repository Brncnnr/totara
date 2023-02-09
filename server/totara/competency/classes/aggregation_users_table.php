<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;


use xmldb_table;

/**
 * Class containing information on the table and columns to use to obtain the users to perform achievement aggregation for
 */
class aggregation_users_table {

    /**
     * @var bool
     */
    protected $is_temporary = false;

    /** @var string $table_name Name of the table containing information on the users to consider for aggregation */
    private $table_name = 'totara_competency_aggregation_queue';

    /** @var string $user_id_column Name of the column containing the ids of users to consider for aggregation */
    private $user_id_column = 'user_id';

    /** @var string $competency_id_column Name of the column containing the ids of competencies to consider for aggregation */
    private $competency_id_column = 'competency_id';

    /** @var ?int $competency_id_value Competency_id value to use in filtering */
    private $competency_id_value = null;

    /** @var string $has_changed_column Name of the column to set for users that have changes. */
    private $has_changed_column = 'has_changed';

    // The process key column and value can be used to allow the same table to be re-used in different processes / instances of
    // the aggregation process.
    // If either the process_key_column or process_key_value is not set, all user ids found in the table are used
    /** @var ?string $process_key_column Name of the column containing the process key. */
    private $process_key_column = 'process_key';

    /** @@var mixed $process_key Key to used for filtering applicable rows */
    protected $process_key_value = '';

    // The update operation column and value can be used to set the name of the operation responsible for the last change
    // Filtering on the update_operation is always secondary to the process_key
    /** @var ?string $update_operation_column Name of the column containing the update operation value. */
    private $update_operation_column = 'update_operation_name';

    /** @@var mixed $update_operation_value Value to used for filtering applicable rows */
    protected $update_operation_value = '';

    /**
     * @param string $table_name
     * @param bool $is_temporary
     * @param string $user_id_column
     * @param string|null $competency_id_column
     * @param string|null $has_changed_column
     * @param string|null $process_key_column
     * @param string|null $update_operation_column
     */
    final public function __construct(
        string $table_name = '',
        bool $is_temporary = false,
        string $user_id_column = '',
        string $competency_id_column = '',
        ?string $has_changed_column = '',
        ?string $process_key_column = '',
        ?string $update_operation_column = ''
    ) {
        // Set defaults for this table
        if (!empty($table_name)) {
            $this->table_name = $table_name;
        }
        if (!empty($user_id_column)) {
            $this->user_id_column = $user_id_column;
        }
        if (!empty($competency_id_column)) {
            $this->competency_id_column = $competency_id_column;
        }
        if (!empty($has_changed_column) || is_null($has_changed_column)) {
            $this->has_changed_column = $has_changed_column;
        }
        if (!empty($process_key_column) || is_null($process_key_column)) {
            $this->process_key_column = $process_key_column;
        }
        if (!empty($update_operation_column) || is_null($update_operation_column)) {
            $this->update_operation_column = $update_operation_column;
        }

        $this->is_temporary = $is_temporary;
        $this->create_temp_table();
    }

    /**
     * Drop temporary table
     */
    public function drop_temp_table() {
        if (!$this->is_temporary) {
            return;
        }

        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/ddllib.php');

        $dbman = $DB->get_manager();
        if ($dbman->table_exists($this->table_name)) {
            $dbman->drop_table(new xmldb_table($this->table_name));
        }
    }

    /**
     * Generate a temporary table or if it already exists truncate it
     */
    private function create_temp_table() {
        if (!$this->is_temporary) {
            return;
        }

        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/ddllib.php');

        $dbman = $DB->get_manager();
        $table = new xmldb_table($this->table_name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field($this->competency_id_column, XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field($this->user_id_column, XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if ($this->has_changed_column) {
            $table->add_field($this->has_changed_column, XMLDB_TYPE_INTEGER, '1');
        }
        if ($this->process_key_column) {
            $table->add_field($this->process_key_column, XMLDB_TYPE_CHAR, '255');
        }
        if ($this->update_operation_column) {
            $table->add_field($this->update_operation_column, XMLDB_TYPE_CHAR, '255');
        }

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $table->add_index('competency_id', XMLDB_INDEX_NOTUNIQUE, ['competency_id']);
        $table->add_index('user_id', XMLDB_INDEX_NOTUNIQUE, [$this->user_id_column]);
        if ($this->process_key_column) {
            $table->add_index('process_key_column', XMLDB_INDEX_NOTUNIQUE, [$this->process_key_column]);
        }
        if ($this->update_operation_column) {
            $table->add_index('update_operation_column', XMLDB_INDEX_NOTUNIQUE, [$this->update_operation_column]);
        }

        if ($dbman->table_exists($table)) {
            $this->truncate();
        } else {
            $dbman->create_temp_table($table);
        }
    }

    /**
     * @return string
     */
    public function get_table_name(): string {
        return $this->table_name;
    }

    /**
     * @return string
     */
    public function get_user_id_column(): string {
        return $this->user_id_column;
    }

    /**
     * @return string
     */
    public function get_competency_id_column(): string {
        return $this->competency_id_column;
    }

    /**
     * @return string|null
     */
    public function get_has_changed_column(): ?string {
        return $this->has_changed_column;
    }

    /**
     * @return string|null
     */
    public function get_process_key_column(): ?string {
        return $this->process_key_column;
    }

    /**
     * Set the process_key value to filter rows on
     *
     * @param string|null $process_key_value
     * @return $this
     */
    public function set_process_key_value(?string $process_key_value): self {
        $this->process_key_value = $process_key_value;
        return $this;
    }

    /**
     * Return the key value
     *
     * @return string|null
     */
    public function get_process_key_value(): ?string {
        return $this->process_key_value;
    }

    /**
     * @return string|null
     */
    public function get_update_operation_column(): ?string {
        return $this->update_operation_column;
    }

    /**
     * Set the update_operation value to filter rows on
     *
     * @param string|null $update_operation_value
     * @return self
     */
    public function set_update_operation_value(?string $update_operation_value): self {
        $this->update_operation_value = $update_operation_value;
        return $this;
    }

    /**
     * Return the currently set update operation value
     *
     * @return string|null
     */
    public function get_update_operation_value(): ?string {
        return $this->update_operation_value;
    }

    /**
     * Set the competency_id value to filter rows on
     *
     * @param int|null $competency_id
     * @return $this
     */
    public function set_competency_id_value(?int $competency_id): self {
        $this->competency_id_value = $competency_id;
        return $this;
    }

    /**
     * Reset the competency_id value to remove filtering
     *
     * @return $this
     */
    public function reset_competency_id_value(): self {
        $this->set_competency_id_value(null);
        return $this;
    }

    /**
     * Return the current competency_id_value used in filtering
     *
     * @return int|null
     */
    public function get_competency_id_value(): ?int {
        return $this->competency_id_value;
    }


    /**
     * Remove all rows from the users table associated with the key
     *
     * @return $this
     */
    public function truncate(): self {
        global $DB;
        $DB->execute("TRUNCATE TABLE {{$this->table_name}}");
        return $this;
    }

    /**
     * Reset the has_changed flag for all users
     *
     * @param mixed $new_has_changed_value Value to set the has_changed_column to
     * @return $this
     */
    public function reset_has_changed($new_has_changed_value): self {
        global $DB;

        if (empty($this->has_changed_column)) {
            return $this;
        }

        $DB->execute(
            'UPDATE {' . $this->table_name . '} SET ' . $this->get_has_changed_column() . ' = :newvalue',
            ['newvalue' => $new_has_changed_value]
        );

        return $this;
    }

    /**
     * Return sql snippet to update the has_changed_column.
     * If an update operation column and value is set, these are also included
     *
     * @param mixed $new_has_changed_value Value to set the has_changed_column to
     * @param  string $table_alias
     * @return array [sql, params] Array containing the column(s) and parameters
     */
    public function get_set_has_changed_sql_with_params($new_has_changed_value, string $table_alias = ''): array {
        $sql = '';
        $params = [];

        if (empty($this->has_changed_column)) {
            return [$sql, $params];
        }

        $table_alias = !empty($table_alias) ? $table_alias . '.' : $table_alias;

        $sql = "{$table_alias}{$this->has_changed_column} = :agtbl_haschanged";
        $params['agtbl_haschanged'] = $new_has_changed_value;

        if (!empty($this->update_operation_column) && !empty($this->update_operation_value)) {
            $sql .= ", {$table_alias}{$this->update_operation_column} = :agtbl_updateoperation";
            $params['agtbl_updateoperation'] = $this->update_operation_value;
        }

        return [$sql, $params];
    }

    /**
     * Return array of column values to use in insert
     * If an update operation column and value is set, these are also included
     *
     * @param int $user_id_value Value to insert in the user_id column
     * @param int $competency_id_value Value to insert in the competency_id column
     * @param mixed|null $has_changed_value Value to set the has_changed_column to
     * @return array
     */
    public function get_insert_record(
        int $user_id_value,
        int $competency_id_value,
        $has_changed_value = null
    ): array {
        $record = [
            $this->get_user_id_column() => $user_id_value,
            $this->competency_id_column => $competency_id_value,
        ];

        if ($this->has_changed_column) {
            $record[$this->get_has_changed_column()] = $has_changed_value ?? 0;
        }

        if (!empty($this->process_key_column) && !empty($this->process_key_value)) {
            $record[$this->process_key_column] = $this->process_key_value;
        }

        if (!empty($this->update_operation_column) && !empty($this->update_operation_value)) {
            $record[$this->update_operation_column] = $this->update_operation_value;
        }

        return $record;
    }

    /**
     * Return the SQL snippet and parameters for inserting
     *
     * @param int|null $user_id_value
     * @param int|null $competency_id_value
     * @param int|null $has_changed_value
     * @return array [string, array]
     */
    public function get_insert_values_sql_with_params(
        ?int $user_id_value = null,
        ?int $competency_id_value = null,
        ?int $has_changed_value = null
    ): array {
        $sql = [];
        $params = [];

        if (!is_null($user_id_value)) {
            $sql[] = ":user_id";
            $params['user_id'] = $user_id_value;
        }

        if (!empty($competency_id_value)) {
            $sql[] = ":competency_id";
            $params['competency_id'] = $competency_id_value;
        }

        if (!is_null($has_changed_value) && $this->has_changed_column) {
            $sql[] = ":has_changed";
            $params['has_changed'] = $has_changed_value;
        }

        if (!empty($this->process_key_column) && !empty($this->process_key_value)) {
            $sql[] =  ":process_key";
            $params['process_key'] = $this->process_key_value;
        }

        return [implode(", ", $sql), $params];
    }

    /**
     * Return the parameters for filtering on the process_key, competency_id and update_operation
     *
     * @param  bool $include_update_operation Include filtering on the update_operation?
     * @return array
     */
    public function get_filter(bool $include_update_operation = true) {
        $filter = [];

        if (!empty($this->competency_id_value)) {
            $filter[$this->competency_id_column] = $this->competency_id_value;
        }

        if (!empty($this->process_key_column) && !empty($this->process_key_value)) {
            $filter[$this->process_key_column] = $this->process_key_value;
        }

        if ($include_update_operation && !empty($this->update_operation_column) && !empty($this->update_operation_value)) {
            $filter[$this->update_operation_column] = $this->update_operation_value;
        }

        return $filter;
    }

    /**
     * Return the SQL snippet and parameters for filtering on the process_key
     *
     * @param string $table_alias
     * @param bool $include_update_operation Include filtering on the update_operation?
     * @param mixed|null $has_change_value Include filtering with this has_changed_value
     * @param string $param_prefix Prefix to use for the parameters
     * @return array [string, array]
     */
    public function get_filter_sql_with_params(
        string $table_alias = '',
        bool $include_update_operation = true,
        $has_change_value = null,
        string $param_prefix = 'autbl'
    ) {
        $sql_parts = [];
        $params = [];

        $table_alias = !empty($table_alias) ? $table_alias . '.' : $table_alias;

        if (!empty($this->competency_id_value)) {
            $sql_parts[] = "{$table_alias}{$this->competency_id_column} = :{$param_prefix}_competency_id";
            $params[$param_prefix . '_competency_id'] = $this->competency_id_value;
        }

        if (!empty($this->process_key_value) && !empty($this->process_key_column)) {
            $sql_parts[] = "{$table_alias}{$this->process_key_column} = :{$param_prefix}_processkey";
            $params[$param_prefix . '_processkey'] = $this->process_key_value;
        }

        if ($include_update_operation && !empty($this->update_operation_column) && !empty($this->update_operation_value)) {
            $sql_parts[] = "{$table_alias}{$this->update_operation_column} = :{$param_prefix}_updateoperation";
            $params[$param_prefix . '_updateoperation'] = $this->update_operation_value;
        }

        if (!is_null($has_change_value) && !empty($this->has_changed_column)) {
            $sql_parts[] = "{$table_alias}{$this->has_changed_column} = :{$param_prefix}_haschanged";
            $params[$param_prefix . '_haschanged'] = $has_change_value;
        }

        return [implode(' AND ', $sql_parts), $params];
    }

    /**
     * Claim all rows with current process key which are not claimed yet
     *
     * @return $this
     */
    public function claim_process() {
        global $DB;

        if ($this->process_key_column) {
            $DB->set_field(
                $this->table_name,
                $this->process_key_column,
                $this->process_key_value,
                [$this->process_key_column => null]
            );
        }

        return $this;
    }

    /**
     * Queue aggregation to be processed in the background, only if no record is already queued
     *
     * @param int $user_id
     * @param int $competency_id
     * @param mixed|null $has_changed_value Value to insert in the has_changed column
     * @return $this
     */
    public function queue_for_aggregation(int $user_id, int $competency_id, $has_changed_value = null): self {
        global $DB;

        $process_key_wh = '';
        if ($this->process_key_column) {
            $process_key_wh = " AND {$this->process_key_column} IS NULL";
        }

        $wh = "{$this->user_id_column} = :qfa_userid
                AND {$this->competency_id_column} = :qfa_competencyid
                {$process_key_wh}";
        $params = [
            'qfa_userid' => $user_id,
            'qfa_competencyid' => $competency_id,
        ];

        $exists = $DB->record_exists_select($this->table_name, $wh, $params);

        if (!$exists) {
            $record = $this->get_insert_record($user_id, $competency_id, $has_changed_value);
            $DB->insert_record($this->table_name, $record);
        } else {
            if (!is_null($has_changed_value) && $this->has_changed_column) {
                // No need to take operation_key, etc. into consideration - busy queueing
                $sql =
                    "UPDATE {{$this->table_name}}
                       SET {$this->has_changed_column} = :qfa_haschanged
                      WHERE {$wh}";
                $params['qfa_haschanged'] = $has_changed_value;
                $DB->execute($sql, $params);
            }
        }

        return $this;
    }

    /**
     * Queue aggregation to be processed in the background for all assigned users
     *
     * @param array $data List of ['user_id' => , 'competency_id' => ] pairs to queue
     * @param mixed|null $has_changed_value Has changed value to set
     * @return array all combinations added to the table (userid, competency_id)
     */
    public function queue_multiple_for_aggregation(array $data, $has_changed_value = null): array {
        global $DB;

        if (empty($data)) {
            return [];
        }

        $has_changed_select = !is_null($has_changed_value) && $this->has_changed_column ? ", {$this->has_changed_column} as has_changed " : '';

        $wh_parts = [];
        $params = [];
        foreach ($data as $idx => $item) {
            // Validate that it has the right properties
            if (!(isset($item['user_id']) && isset($item['competency_id']))) {
                throw new \coding_exception('Data passed to queue_multiple_for_aggregation must contain a user_id and competency_id');
            }
            // To ensure consistency convert it to an object
            $item = (object) $item;
            $data[$idx] = $item;

            $wh_parts[] = "{$this->user_id_column} = :userid_{$idx} AND {$this->competency_id_column} = :compid_{$idx}";
            $params["userid_{$idx}"] = $item->user_id;
            $params["compid_{$idx}"] = $item->competency_id;
        }
        $user_competency_condition = "((" . implode(') OR (', $wh_parts) . "))";

        $process_key_wh = '';
        if ($this->process_key_column) {
            $process_key_wh = " AND {$this->process_key_column} IS NULL";
        }

        $sql = "
            SELECT id, {$this->get_user_id_column()}, {$this->get_competency_id_column()} {$has_changed_select}
            FROM {{$this->get_table_name()}}
            WHERE {$user_competency_condition} {$process_key_wh}
        ";

        $existing_rows = $DB->get_records_sql($sql, $params);
        // Get all rows which are not there already
        $to_add = array_udiff($data, $existing_rows, function ($new, $existing) {
            if ($new->user_id == $existing->{$this->get_user_id_column()}) {
                return $new->competency_id <=> $existing->{$this->get_competency_id_column()};
            } else {
                return $new->user_id <=> $existing->{$this->get_user_id_column()};
            }
        });

        $to_update = [];

        if (!is_null($has_changed_value) && $this->has_changed_column) {
            $to_update = array_udiff($existing_rows, $to_add, function ($existing, $new) {
                if ($existing->user_id == $new->{$this->get_user_id_column()}) {
                    return $existing->competency_id <=> $new->{$this->get_competency_id_column()};
                } else {
                    return $existing->user_id <=> $new->{$this->get_user_id_column()};
                }
            });

            $to_update = array_filter($to_update, function ($v) use ($has_changed_value) {
                return $v->has_changed != $has_changed_value;
            });
        }

        if (!empty($to_add)) {
            // Prepare insert array, ensuring that only user, competency id and has_changed are there
            $to_add = array_map(function ($item) {
                $add_el = (object)$this->get_insert_record($item->user_id, $item->competency_id, $item->has_changed ?? null);
                // We are queueing - process key is not needed
                unset($add_el->{$this->process_key_column});
                return $add_el;
            }, $to_add);

            $DB->insert_records_via_batch($this->table_name, $to_add);
        }

        if (!empty($to_update)) {
            $wh_parts = [];
            $params = ['haschanged' => $has_changed_value];

            foreach ($to_update as $idx => $item) {
                $wh_parts[] = "{$this->user_id_column} = :userid_{$idx} AND {$this->competency_id_column} = :compid_{$idx}";
                $params["userid_{$idx}"] = $item->user_id;
                $params["compid_{$idx}"] = $item->competency_id;
            }
            $update_wh = "((" . implode(') OR (', $wh_parts) . "))";

            $DB->execute(
                "UPDATE {{$this->table_name}}
                         SET {$this->has_changed_column} = :haschanged
                       WHERE {$update_wh} {$process_key_wh}",
                $params
            );
        }

        return array_merge($to_add, $to_update);
    }

    /**
     * Queue aggregation to be processed in the background for all assigned users
     *
     * @param int $competency_id
     * @param mixed|null $has_changed_value Value to set the has_changed_column to
     * @return $this
     */
    public function queue_all_assigned_users_for_aggregation(int $competency_id, $has_changed_value = null): self {
        global $DB;

        $assignment_users_table = aggregation_helper::get_assigned_users_sql_table();

        $process_key_wh = '';
        if ($this->process_key_column) {
            $process_key_wh = " AND agg_queue.{$this->process_key_column} IS NULL";
        }

        $sql =
            "INSERT INTO {{$this->get_table_name()}}
                ({$this->user_id_column}, {$this->competency_id_column})
                SELECT DISTINCT tcau.user_id, tcau.competency_id
                       FROM {$assignment_users_table} tcau
                  LEFT JOIN {{$this->get_table_name()}} agg_queue
                         ON agg_queue.{$this->competency_id_column} = tcau.competency_id
                        AND agg_queue.{$this->user_id_column} = tcau.user_id
                            {$process_key_wh}
                      WHERE tcau.competency_id = :compid
                        AND agg_queue.id IS NULL";

        $DB->execute($sql, ['compid' => $competency_id]);

        if (!is_null($has_changed_value) && $this->has_changed_column) {
            if ($this->process_key_column) {
                $process_key_wh = " AND {$this->process_key_column} IS NULL";
            }
            $DB->execute(
                "UPDATE {{$this->table_name}}
                         SET {$this->has_changed_column} = :haschanged
                       WHERE {$this->competency_id_column} = :compid
                             {$process_key_wh}",
                ['haschanged' => $has_changed_value, 'compid' => $competency_id]
            );
        }

        return $this;
    }

    /**
     * delete all rows belonging to the current process
     *
     * @return $this
     */
    public function delete(): self {
        global $DB;

        $params = [];
        if ($this->process_key_column && $this->process_key_value) {
            $params[$this->process_key_column] = $this->process_key_value;
        }

        $DB->delete_records($this->table_name, $params);

        return $this;
    }

    /**
     * Find out if there is any aggregation pending for the given user.
     *
     * @param int $user_id
     * @param int|null $competency_id  When given, only check for the user/competency combination.
     * @return bool
     */
    public function has_pending_aggregation(int $user_id, int $competency_id = null): bool {
        global $DB;

        $conditions = ['user_id' => $user_id];
        if ($competency_id) {
            $conditions['competency_id'] = $competency_id;
        }

        return $DB->record_exists($this->table_name, $conditions);
    }
}
