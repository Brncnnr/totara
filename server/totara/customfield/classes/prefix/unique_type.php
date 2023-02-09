<?php
/*
 * This file is part of Totara Learn
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_customfield
 */

namespace totara_customfield\prefix;

/**
 * Trait unique_type
 *
 * @package totara_customfield\prefix
 */
trait unique_type {

    /**
     * Move a customfield up or down.
     *
     * This has been overridden because hierarchies have types.
     * We need to take these types into account as well.
     * The sortorder *should* be unique by type.
     *
     * @param int $id ID of the customfield we want to move.
     * @param string $move the direction - 'up' or 'down'.
     * @return bool
     */
    public function move($id, $move) {
        global $DB;

        $tableprefix = $this->get_table_prefix();
        $field = static::get_field_to_move($tableprefix, $id);

        if ($move === 'up') {
            $sql = "SELECT id,typeid,sortorder
                      FROM {{$tableprefix}_info_field} cif
                     WHERE typeid = :typeid AND
                           sortorder < :sortorder
                  ORDER BY sortorder DESC";
        } else {
            $move = 'down';
            $sql = "SELECT id,typeid,sortorder
                      FROM {{$tableprefix}_info_field} cif
                     WHERE typeid = :typeid AND
                           sortorder > :sortorder
                  ORDER BY sortorder ASC";
        }

        $params = ['typeid' => $field->typeid, 'sortorder' => $field->sortorder];
        $swapfields = $DB->get_records_sql($sql, $params, 0, 1);

        if (count($swapfields) !== 1) {
            debugging('Invalid action, the selected field cannot be moved '.$move, DEBUG_DEVELOPER);
            return false;
        }
        $swapfield = reset($swapfields);

        $holding = $field->sortorder;
        $field->sortorder = $swapfield->sortorder;
        $swapfield->sortorder = $holding;

        // Always together.
        $transaction = $DB->start_delegated_transaction();
        $DB->update_record($tableprefix.'_info_field', $field);
        $DB->update_record($tableprefix.'_info_field', $swapfield);
        $transaction->allow_commit();

        // Finally re-order all fields, just to be safe.
        // Needed because those on earlier versions may have unbalanced sortorders to begin with.
        $this->reorder_fields();
        return true;
    }

    /**
     * Get an array of conditions to look for fields
     *
     * @param $neworder
     * @param $field
     * @return array
     */
    public static function get_conditions_swapfields($neworder, $field) {
        return array('sortorder' => $neworder, 'typeid' => $field->typeid);
    }

    /**
     * Get the field record to move.
     *
     * @param $tableprefix
     * @param $id
     * @return mixed
     */
    public static function get_field_to_move($tableprefix, $id) {
        global $DB;
        return $DB->get_record($tableprefix.'_info_field', array('id' => $id), 'id, typeid, sortorder');
    }

    /**
     * Reordering fields in database
     *
     * @return bool Result of the action executed.
     */
    public function reorder_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/core/utils.php');
        $tableprefix = $this->get_table_prefix();
        $rs = $DB->get_recordset($tableprefix . '_info_field', array(), 'sortorder ASC');
        if ($types = totara_group_records($rs, 'typeid')) {
            foreach ($types as $unused => $fields) {
                $i = 1;
                foreach ($fields as $field) {
                    $f = new \stdClass();
                    $f->id = $field->id;
                    $f->sortorder = $i++;
                    $DB->update_record($tableprefix.'_info_field', $f);
                }
            }
        }
        $rs->close();
        return true;
    }

    public function get_fields_sql_where() {
        return array('typeid' => $this->get_other()['typeid']);
    }

    /**
     * Returns the sortorder value a new field should use.
     * @return int
     */
    public function get_next_sortorder() {
        global $DB;
        $sql = "SELECT id, sortorder
                  FROM {{$this->get_table_prefix()}_info_field}
                 WHERE typeid = :typeid
              ORDER BY sortorder DESC";
        $result = $DB->get_records_sql($sql, ['typeid' => $this->get_other()['typeid']], 0, 1);
        if (empty($result)) {
            // It will be the first field.
            return 1;
        } else {
            $record = reset($result);
            return $record->sortorder + 1;
        }
    }

    abstract public function get_other();

    abstract public function get_table_prefix();
}