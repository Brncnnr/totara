<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;

/**
 * Simple class that implements the {@link moodle_recordset} API based on an
 * array of test data.
 *
 *  See the {@link question_attempt_step_db_test} class in
 *  question/engine/tests/testquestionattemptstep.php for an example of how
 *  this is used.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_question_test_recordset extends moodle_recordset {
    protected $records;

    /**
     * Constructor
     * @param $table as for {@link testing_db_record_builder::build_db_records()}
     *      but does not need a unique first column.
     */
    public function __construct(array $table) {
        $columns = array_shift($table);
        $this->records = array();
        foreach ($table as $row) {
            if (count($row) != count($columns)) {
                throw new coding_exception("Row contains the wrong number of fields.");
            }
            $rec = array();
            foreach ($columns as $i => $name) {
                $rec[$name] = $row[$i];
            }
            $this->records[] = $rec;
        }
        reset($this->records);
    }

    public function __destruct() {
        $this->close();
    }

    #[\ReturnTypeWillChange]
    public function current() {
        return (object) current($this->records);
    }

    #[\ReturnTypeWillChange]
    public function key() {
        if (is_null(key($this->records))) {
            return false;
        }
        $current = current($this->records);
        return reset($current);
    }

    #[\ReturnTypeWillChange]
    public function next() {
        next($this->records);
    }

    #[\ReturnTypeWillChange]
    public function valid() {
        return !is_null(key($this->records));
    }

    public function close() {
        $this->records = null;
    }
}
