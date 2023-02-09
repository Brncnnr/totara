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

/**
 * Delegated database transaction support.
 *
 * @package    core_dml
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Delegated transaction class.
 *
 * @package    core_dml
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class moodle_transaction {
    /** @var array The debug_backtrace() returned array.*/
    private $start_backtrace;
    /**@var moodle_database The moodle_database instance.*/
    private $database = null;
    /** @var string name of transaction */
    private $name;

    /**
     * Delegated transaction constructor,
     * can be called only from moodle_database class.
     * Unfortunately PHP's protected keyword is useless.
     * @internal
     * @param moodle_database $database
     */
    public function __construct(moodle_database $database, string $name) {
        $this->database = $database;
        $this->name = $name;
        $this->start_backtrace = debug_backtrace();
        array_shift($this->start_backtrace);
    }

    /**
     * Returns backtrace of the code starting exception.
     * @return array
     */
    public function get_backtrace() {
        return $this->start_backtrace;
    }

    /**
     * Name of transaction.
     *
     * @since Totara 13
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Is the delegated transaction already used?
     * @return bool true if commit and rollback allowed, false if already done
     */
    public function is_disposed() {
        return empty($this->database);
    }

    /**
     * Mark transaction as disposed, no more
     * commits and rollbacks allowed.
     * To be used only from moodle_database class
     * @return null
     */
    public function dispose() {
        return $this->database = null;
    }

    /**
     * Commit transaction if outermost,
     * release savepoint for nested transactions.
     *
     * The real database commit SQL is executed
     * only after committing all delegated transactions.
     *
     * Incorrect order of nested commits at any level
     * results in forced rollback of all transactions.
     *
     * @return void
     */
    public function allow_commit() {
        if ($this->is_disposed()) {
            throw new dml_transaction_exception('Transactions already disposed', $this);
        }
        $this->database->commit_delegated_transaction($this);
    }

    /**
     * Rollback transaction if outermost,
     * rollback to savepoint for nested transactions.
     *
     * Outer rollback invalidates all its nested transactions,
     * invalid rollback call results in forced rollback of all transactions.
     *
     * @param Throwable|null $e optional exception/throwable
     * @throws Throwable if $e provided it is rethrown after successful rollback
     * @return void
     */
    public function rollback(Throwable $e = null) {
        if ($this->is_disposed()) {
            throw new dml_transaction_exception('Transactions already disposed', $this);
        }
        $this->database->rollback_delegated_transaction($this, $e);
    }
}
