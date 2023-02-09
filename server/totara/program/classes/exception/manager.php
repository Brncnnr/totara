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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\exception;

class manager {

    const EXCEPTIONTYPE_TIME_ALLOWANCE          = 1;
    const EXCEPTIONTYPE_ALREADY_ASSIGNED        = 2;
    const EXCEPTIONTYPE_COMPLETION_TIME_UNKNOWN = 4;
    const EXCEPTIONTYPE_UNKNOWN                 = 5;
    const EXCEPTIONTYPE_DUPLICATE_COURSE        = 6;

    const SELECTIONTYPE_NONE                    = 0;
    const SELECTIONTYPE_ALL                     = -1;
    const SELECTIONTYPE_TIME_ALLOWANCE          = 1;
    const SELECTIONTYPE_ALREADY_ASSIGNED        = 2;
    const SELECTIONTYPE_COMPLETION_TIME_UNKNOWN = 4;
    const SELECTIONTYPE_DUPLICATE_COURSE        = 5;

    const SELECTIONACTION_NONE                = 0;
    const SELECTIONACTION_AUTO_TIME_ALLOWANCE = 1;
    const SELECTIONACTION_OVERRIDE_EXCEPTION  = 2;
    const SELECTIONACTION_DISMISS_EXCEPTION   = 3;

    const RESULTS_PER_PAGE = 50;

    protected $programid;
    protected $selectedexceptions;

    public  $exceptiontype_classnames;
    private $exceptiontype_descriptors;
    private $exception_actions;

    /**
     * Class manager constructor.
     *
     * @param int $programid
     */
    function __construct(int $programid) {
        $this->programid = $programid;
        $this->selectedexceptions = [];

        $this->exceptiontype_classnames = [
            self::EXCEPTIONTYPE_TIME_ALLOWANCE          => time_allowance::class,
            self::EXCEPTIONTYPE_ALREADY_ASSIGNED        => already_assigned::class,
            self::EXCEPTIONTYPE_COMPLETION_TIME_UNKNOWN => completion_time_unknown::class,
            self::EXCEPTIONTYPE_UNKNOWN                 => unknown::class,
            self::EXCEPTIONTYPE_DUPLICATE_COURSE        => duplicate_course::class,
        ];

        $this->exceptiontype_descriptors = [
            self::EXCEPTIONTYPE_TIME_ALLOWANCE          => get_string('timeallowance', 'totara_program'),
            self::EXCEPTIONTYPE_ALREADY_ASSIGNED        => get_string('exceptiontypealreadyassigned', 'totara_program'),
            self::EXCEPTIONTYPE_COMPLETION_TIME_UNKNOWN => get_string('completiontimeunknown', 'totara_program'),
            self::EXCEPTIONTYPE_UNKNOWN                 => get_string('unknownexception', 'totara_program'),
            self::EXCEPTIONTYPE_DUPLICATE_COURSE        => get_string('exceptiontypeduplicatecourse', 'totara_program'),
        ];

        $this->exception_actions = [
            self::SELECTIONACTION_NONE,
            self::SELECTIONACTION_AUTO_TIME_ALLOWANCE,
            self::SELECTIONACTION_OVERRIDE_EXCEPTION,
            self::SELECTIONACTION_DISMISS_EXCEPTION,
        ];
    }

    public function get_selected_exceptions(): array {
        return $this->selectedexceptions;
    }

    /**
     * Adds a new exception.
     *
     * @param int $exceptiontype
     * @param int $userid
     * @param int $assignmentid
     * @param int $timeraised
     *
     * @return bool
     */
    public function raise_exception($exceptiontype, $userid, $assignmentid, $timeraised = null) {
        if (base::exception_exists($this->programid, $exceptiontype, $userid)) {
            // Return true if this exception has already been raised
            return true;
        }

        return base::insert_exception($this->programid, $exceptiontype, $userid, $assignmentid, $timeraised);
    }

    /**
     * Override and complete the assignment for a user with a previously dismissed exception.
     * This should never be used, however dismissed exceptions are handled badly so we have to
     *
     * @param int userid
     *
     * @return bool
     */
    public function override_dismissed_exception(int $userid): bool {
        global $DB;

        $assignparams = ['programid' => $this->programid, 'userid' => $userid];
        $assignments = $DB->get_records('prog_user_assignment', $assignparams);
        if (!empty($assignments)) {
            // Update all the dismissed exceptions at once.
            $assignsql = 'UPDATE {prog_user_assignment}
                             SET exceptionstatus = ' . PROGRAM_EXCEPTION_RESOLVED . '
                           WHERE exceptionstatus = ' . PROGRAM_EXCEPTION_DISMISSED . '
                             AND userid = :userid
                             AND programid = :programid';
            $DB->execute($assignsql, $assignparams);
        } else {
            throw new \coding_exception(get_string('error:assignmentnotfound', 'totara_program'));
        }

        // There shouldn't be any exception records here but just to be safe.
        $exceptions = $DB->get_records('prog_exception', $assignparams);
        foreach ($exceptions as $exception) {
            // Only delete the ones we have edited above.
            if ($assignments[$exception->assignmentid]->exceptionstatus === PROGRAM_EXCEPTION_DISMISSED) {
                base::delete_exception($exception->id);
            }
        }

        // Record the change in the program completion log.
        prog_log_completion(
            $this->programid,
            $userid,
            'Program Assignment Exception - Overridden a previously dismissed exception'
        );

        // Event trigger to send notification when exception is resolved.
        $event = \totara_program\event\program_assigned::create(
            [
                'objectid' => $this->programid,
                'context'  => \context_program::instance($this->programid),
                'userid'   => $userid,
            ]
        );
        $event->trigger();

        return true;
    }

    /**
     * Deletes an exception from the database
     *
     * @param int $exceptionid
     *
     * @return bool
     */
    public function delete_exception(int $exceptionid): bool {
        return base::delete_exception($exceptionid);
    }

    /**
     * Deletes all exceptions and exception data relating to a specific assignment
     * from the database
     *
     * @param int $assignmentid
     * @param int $userid (optional)
     *
     * @return bool Success status
     */
    public static function delete_exceptions_by_assignment(int $assignmentid, int $userid = 0): bool {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $exceptionselect = "assignmentid = ?";
        $params = [$assignmentid];
        if ($userid) {
            $exceptionselect .= " AND userid = ?";
            $params[] = $userid;
        }

        $DB->delete_records_select('prog_exception', $exceptionselect, $params);
        // Deleted exceptions, now update exception status for user assignments.
        $update_sql = "UPDATE {prog_user_assignment} SET exceptionstatus = 0 WHERE assignmentid = ?";
        $params = [$assignmentid];
        if ($userid) {
            $update_sql .= " AND userid = ?";
            $params[] = $userid;
        }

        $DB->execute($update_sql, $params);
        $transaction->allow_commit();

        return true;
    }

    /**
     * Deletes all exceptions for this program
     *
     * @return true
     */
    public function delete(): bool {
        global $DB;

        return $DB->delete_records('prog_exception', ['programid' => $this->programid]);
    }

    public function count_exceptions(): int {
        global $DB;

        $sql = "SELECT COUNT(ex.id)
                  FROM {prog_exception} ex
            INNER JOIN {user} us ON us.id = ex.userid
                 WHERE ex.programid = ? AND us.deleted = 0";

        return $DB->count_records_sql($sql, [$this->programid]);
    }

    /**
     * Creates an array containing the ids of all the exceptions that match a specific selection criteria.
     *
     * @param int    $selectiontype
     * @param string $searchterm
     *
     * @return bool
     */
    public function set_selections(int $selectiontype, string $searchterm = '') {
        if ($selectiontype == self::SELECTIONTYPE_ALL) {
            $this->selectedexceptions = $this->search_exceptions('all', $searchterm);
        } else if ($selectiontype == self::SELECTIONTYPE_NONE) {
            $this->selectedexceptions = [];
        } else {
            switch ($selectiontype) {
                case self::SELECTIONTYPE_TIME_ALLOWANCE:
                    $exceptiontype = self::EXCEPTIONTYPE_TIME_ALLOWANCE;
                    break;
                case self::SELECTIONTYPE_ALREADY_ASSIGNED:
                    $exceptiontype = self::EXCEPTIONTYPE_ALREADY_ASSIGNED;
                    break;
                case self::SELECTIONTYPE_COMPLETION_TIME_UNKNOWN:
                    $exceptiontype = self::EXCEPTIONTYPE_COMPLETION_TIME_UNKNOWN;
                    break;
                case self::SELECTIONTYPE_DUPLICATE_COURSE:
                    $exceptiontype = self::EXCEPTIONTYPE_DUPLICATE_COURSE;
                    break;
                default:
                    $exceptiontype = self::EXCEPTIONTYPE_UNKNOWN;
                    break;
            }
            $this->selectedexceptions = $this->search_exceptions('all', $searchterm, $exceptiontype);
        }

        return true;
    }

    public function search_exceptions($page = 'all', $searchterm = '', $exceptiontype = '', $count = false) {
        global $DB;

        $usernamefields = get_all_user_name_fields(true, 'us', null, 'user_');

        $fields = "ex.*, {$usernamefields}, us.id as userid";
        if ($count) {
            $fields = 'COUNT(ex.id)';
        }

        $sql = "SELECT $fields
        FROM {prog_exception} ex
        INNER JOIN {user} us ON us.id = ex.userid
        WHERE ex.programid = ? AND us.deleted = 0";
        $params = [$this->programid];
        if (!empty($exceptiontype)) {
            $sql .= " AND ex.exceptiontype = ?";
            $params[] = $exceptiontype;
        }

        if (!empty($searchterm)) {
            if (is_numeric($searchterm)) {
                $sql .= " AND us.id = ?";
                $params[] = $searchterm;
            } else {
                $sql .= " AND " . $DB->sql_like($DB->sql_concat('us.firstname', "' '", 'us.lastname'), '?', false);
                $params[] = '%' . $DB->sql_like_escape($searchterm) . '%';
            }
        }

        if ($count) {
            return $DB->count_records_sql($sql, $params);
        }

        $limit = is_int($page) ? self::RESULTS_PER_PAGE : '';
        $offset = is_int($page) ? (($page) * self::RESULTS_PER_PAGE) : '';

        $exceptions = $DB->get_records_sql($sql, $params, $offset, $limit);

        return $exceptions;
    }

    public function print_exceptions_form($programid, $exceptions, $selectedexceptions = null, $selectiontype = self::SELECTIONTYPE_NONE, $iscertif = false) {
        global $PAGE;
        $numexceptions = count($exceptions);
        $numselectedexceptions = count($selectedexceptions);

        $tabledata = [];

        foreach ($exceptions as $exception) {
            $rowdata = new \stdClass();

            $user = new \stdClass();
            $user->id = $exception->userid;
            foreach (get_all_user_name_fields() as $field) {
                $varname = "user_{$field}";
                $user->$field = $exception->$varname;
            }
            $rowdata->exceptionid = $exception->id;
            $rowdata->user = $user;
            $rowdata->firstname = $user->firstname;
            $rowdata->lastname = $user->lastname;
            $rowdata->selected = isset($selectedexceptions[$exception->id]) ? true : false;

            if (isset($this->exceptiontype_descriptors[$exception->exceptiontype])) {
                $exceptiontype = $exception->exceptiontype;
                $descriptor = $this->exceptiontype_descriptors[$exceptiontype];
            } else {
                $exceptiontype = self::EXCEPTIONTYPE_UNKNOWN;
                $descriptor = $this->exceptiontype_descriptors[$exceptiontype];
            }

            $rowdata->exceptiontype = $exceptiontype;
            $rowdata->descriptor = $descriptor;

            $tabledata[] = $rowdata;
        }
        $renderer = $PAGE->get_renderer('totara_program');
        echo $renderer->print_exceptions_form($numexceptions, $numselectedexceptions, $programid, $selectiontype, $tabledata, $iscertif);
    }

    /**
     * Get a multidimensional array of the different exception types and the actions
     * that they handle. Can be returned as an array or as a JSON encoded string
     *
     * @param string $returntype
     *
     * @return array|string
     */
    public function get_handled_actions(string $returntype = 'array') {
        // Build a list of exceptions and their handled actions
        $handledActions = [];
        foreach ($this->exceptiontype_classnames as $exception_class) {
            $exception = new $exception_class($this->programid);
            $handledActions[$exception->exceptiontype] = [];
            foreach ($this->exception_actions as $action) {
                $handledActions[$exception->exceptiontype][$action] = $exception->handles($action);
            }
        }

        if ($returntype == 'json') {
            return json_encode($handledActions);
        } else {
            return $handledActions;
        }
    }

    /**
     * Get an array specifying which of the defined actions can be handled by the currently
     * selectedexceptions. Can be returned as an array or as a JSON encoded string.
     *
     * @param string $returntype
     * @param array  $selectedexceptions
     *
     * @return array|string
     */
    public function get_handled_actions_for_selection(string $returntype = 'array', array $selectedexceptions = null) {
        if ($selectedexceptions == null) {
            $selectedexceptions = $this->selectedexceptions;
        }

        if (empty($selectedexceptions)) {
            $handledActions = [
                self::SELECTIONACTION_AUTO_TIME_ALLOWANCE => false,
                self::SELECTIONACTION_OVERRIDE_EXCEPTION  => false,
                self::SELECTIONACTION_DISMISS_EXCEPTION   => false,
            ];
        } else {
            // Build a list of exceptions and their handled actions
            $handledActions = [
                self::SELECTIONACTION_AUTO_TIME_ALLOWANCE => true,
                self::SELECTIONACTION_OVERRIDE_EXCEPTION  => true,
                self::SELECTIONACTION_DISMISS_EXCEPTION   => true,
            ];

            foreach ($selectedexceptions as $selectedexception) {
                if (isset($this->exceptiontype_classnames[$selectedexception->exceptiontype])) {
                    $classname = $this->exceptiontype_classnames[$selectedexception->exceptiontype];
                } else {
                    $classname = $this->exceptiontype_classnames[self::EXCEPTIONTYPE_UNKNOWN];
                }

                $exceptionob = new $classname($this->programid, $selectedexception);

                foreach ($handledActions as $action => $handles) {
                    if (!$exceptionob->handles($action)) {
                        $handledActions[$action] = false;
                    }
                }
            }
        }

        if ($returntype == 'json') {
            return json_encode($handledActions);
        } else {
            return $handledActions;
        }
    }

}
