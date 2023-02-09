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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */

/**
 * This file contains sqlhandler for rules based on certification status
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Handles rules for certification status.
 */
final class cohort_rule_sqlhandler_certification_status extends cohort_rule_sqlhandler {

    // Statuses.
    const CERTIFIED         = 10;
    const EXPIRED           = 20;
    const NEVER_CERTIFIED   = 30;

    // Assignment statuses.
    const ASSIGNED          = 10;
    const UNASSIGNED        = 20;

    /**
     * @var array
     */
    public $params = [
        'status'            => '0',
        'assignmentstatus'  => '0',
        'listofids'         => '1'
    ];

    /**
     * @var int Count of certifications included.
     */
    public $certcount = 0;

    /**
     * @var array A list of certification IDs.
     */
    private $certids;

    /**
     * Get array or certification statuses
     *
     * @return array
     */
    public static function statuses() {
        return [
            self::CERTIFIED         => 'ruledesc-learning-certificationstatus-currentlycertified',
            self::EXPIRED           => 'ruledesc-learning-certificationstatus-currentlyexpired',
            self::NEVER_CERTIFIED   => 'ruledesc-learning-certificationstatus-nevercertified'
        ];
    }

    /**
     * Get array or certification assignment statuses
     *
     * @return array
     */
    public static function assignment_statuses() {
        return [
            self::ASSIGNED    => 'ruledesc-learning-certificationstatus-assigned',
            self::UNASSIGNED  => 'ruledesc-learning-certificationstatus-unassigned'
        ];
    }

    /**
     * Get the status
     *
     * @param $status
     * @return bool|mixed
     */
    public static function get_status($status) {
       if (isset(self::statuses()[$status])) {
            return self::statuses()[$status];
       }
       return false;
    }

    /**
     * Get the assignment status
     *
     * @param $status
     * @return bool|mixed
     */
    public static function get_assignment_status($status) {
        if (isset(self::assignment_statuses()[$status])) {
            return self::assignment_statuses()[$status];
        }
        return false;
    }

    /**
     * Create the rule sql
     *
     * @return stdClass
     * @throws coding_exception
     */
    public function get_sql_snippet() {
        global $DB;

        // Do some checks.
        if (empty($this->listofids) || empty($this->status) || empty($this->assignmentstatus)) {
            // We should never get here.
            throw new \coding_exception('Dynamic audience certification rule has missing parameters');
        }

        // Transform programs IDs into certification IDs to avoid extra joins later.
        list($sqlin, $params) = $DB->get_in_or_equal($this->listofids, SQL_PARAMS_NAMED, 'ns_'.$this->ruleid);
        $this->certids = $DB->get_records_select_menu('prog', "id {$sqlin}", $params, '', 'id, certifid');

        // Statuses array.
        $statuses = explode(',', $this->status);

        // Check all statuses are valid.
        array_walk($statuses, function($status){
            if (!self::get_status($status)) {
                // Status is invalid.
                throw new \coding_exception('Dynamic audience certification rule has invalid status');
            }
        });

        // Assignment statuses.
        $assignmentstatus = explode(',', $this->assignmentstatus);

        // Check all assignment statuses are valid.
        array_walk($assignmentstatus, function($status){
            if (!self::get_assignment_status($status)) {
                // Assignment status is invalid.
                throw new \coding_exception('Dynamic audience certification rule has invalid assignment status');
            }
        });

        // Set count of certifications included.
        $this->certcount = count($this->listofids);

        // Database params.
        $all_params = [];

        //
        // Statuses.
        //

        // If all statuses are selected.
        if (count($statuses) == count(self::statuses())) {
            $status_sql = "1=1";
        } else {
            $sql_chunks = [];

            // Currently certified.
            if (in_array(self::CERTIFIED, $statuses)) {
                list($sql, $params) = $this->get_sql_snippet_certified();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Currently expired.
            if (in_array(self::EXPIRED, $statuses)) {
                list($sql, $params) = $this->get_sql_snippet_expired();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Never certified.
            if (in_array(self::NEVER_CERTIFIED, $statuses)) {
                list($sql, $params) = $this->get_sql_snippet_never_certified();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Join together using OR.
            $status_sql = "(" . implode(' OR ', $sql_chunks) . ")";
        }

        //
        // Assignment statuses.
        //

        // If all statuses are selected.
        if (count($assignmentstatus) == count(self::assignment_statuses())) {
            $assignments_status_sql = "1=1";
        } else {
            $sql_chunks = [];

            // Assigned.
            if (in_array(self::ASSIGNED, $assignmentstatus)) {
                list($sql, $params) = $this->get_sql_snippet_assigned();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Unassigned.
            if (in_array(self::UNASSIGNED, $assignmentstatus)) {
                list($sql, $params) = $this->get_sql_snippet_unassigned();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Join together using OR.
            $assignments_status_sql = "(" . implode(' OR ', $sql_chunks) . ")";
        }

        // Create the $sqlhandler object.
        $sqlhandler = new stdClass();
        $sqlhandler->sql = " ({$status_sql}) AND ({$assignments_status_sql}) ";
        $sqlhandler->params = $all_params;


        // Example sqlhandler sql structure.
        //
        //  (
        //     (certified sql)
        //     OR
        //     (expired sql)
        //     OR
        //     (never certified sql)
        //  ) AND (
        //     (assigned sql)
        //     OR
        //     (unassigned sql)
        //  )

        return $sqlhandler;
    }

    /**
     * Get the certified sql snippet
     *
     * @return array
     */
    private function get_sql_snippet_certified() {
        // NOTE: this must match logic in totara/plan/rb_sources/rb_source_dp_certification.php
        //       and totara/certification/classes/rb/display/certif_status.php

        $timenow = time();
        $sqlchunks = [];
        foreach ($this->certids as $certifid) {
            $certifid = intval($certifid);
            $sqlchunks[] = "(
            EXISTS (SELECT 1
                      FROM {certif_completion} cc
                     WHERE cc.certifid = {$certifid} AND cc.userid = u.id
                           AND (cc.status = " . CERTIFSTATUS_COMPLETED . " OR cc.status = " . CERTIFSTATUS_INPROGRESS . ")
                           AND cc.timecompleted > 0 AND cc.timeexpires > {$timenow}
            ) OR
            EXISTS (SELECT 1
                      FROM {certif_completion_history} cch
                 LEFT JOIN {certif_completion} cc ON cc.userid = cch.userid AND cc.certifid = cch.certifid
                     WHERE cch.certifid = {$certifid} AND cch.userid = u.id
                           AND (cch.status = " . CERTIFSTATUS_COMPLETED . " OR cch.status = " . CERTIFSTATUS_INPROGRESS . ")
                           AND cch.timecompleted > 0 AND cch.timeexpires > {$timenow}
                           AND cch.unassigned = 1 AND cc.id IS NULL
            ))";
        }

        $sql = implode(' AND ', $sqlchunks);

        return [$sql, []];
    }

    /**
     * Get the expired sql snippet
     *
     * @return array
     */
    private function get_sql_snippet_expired() {
        $timenow = time();
        $sqlchunks = [];
        foreach ($this->certids as $certifid) {
            $certifid = intval($certifid);

            $sqlchunks[] = "(
            EXISTS (SELECT 1
                      FROM {certif_completion} cc
                     WHERE cc.certifid = {$certifid} AND cc.userid = u.id
                           AND (
                                (cc.status = " . CERTIFSTATUS_COMPLETED . " AND cc.timeexpires < {$timenow})
                                OR cc.status = " . CERTIFSTATUS_EXPIRED . "
                                OR (cc.status = " . CERTIFSTATUS_INPROGRESS . " AND cc.renewalstatus = " . CERTIFRENEWALSTATUS_EXPIRED . ")
                           )
            ) OR 
            EXISTS (SELECT 1
                      FROM {certif_completion_history} cch
                 LEFT JOIN {certif_completion} cc ON cc.certifid = cch.certifid AND cc.userid = cch.userid AND cc.timecompleted != 0 AND cc.timeexpires > {$timenow}
                     WHERE cch.certifid = {$certifid} AND cch.userid = u.id AND cc.id IS NULL
                           AND cch.timecompleted != 0 AND cch.timeexpires < {$timenow}
            ))";
        }

        $sql = implode(' AND ', $sqlchunks);

        return [$sql, []];
    }

    /**
     * Get the never certified sql snippet
     *
     * @return array
     */
    private function get_sql_snippet_never_certified() {
        global $DB;

        list($sqlin1, $params1) = $DB->get_in_or_equal($this->certids, SQL_PARAMS_NAMED, 'nsc_certifid');
        list($sqlin2, $params2) = $DB->get_in_or_equal($this->certids, SQL_PARAMS_NAMED, 'nsh_certifid');

        $sql = "
        NOT EXISTS (SELECT 1
                      FROM {certif_completion} cc
                     WHERE cc.certifid {$sqlin1} AND cc.userid = u.id AND cc.timecompleted != 0
        ) AND
        NOT EXISTS (SELECT 1
                      FROM {certif_completion_history} cch
                     WHERE cch.certifid {$sqlin2} AND cch.userid = u.id AND cch.timecompleted != 0
        )";

        return [$sql, array_merge($params1, $params2)];
    }

    /**
     * Get the assigned sql snippet
     *
     * @return array
     */
    private function get_sql_snippet_assigned() {
        global $DB;

        $sqlchunks = [];
        $params = [];
        foreach ($this->listofids as $certifid) {
            $sqlprefix = $DB->get_unique_param('as');

            $sqlchunks[] = "EXISTS (SELECT 1
                              FROM {prog_user_assignment} pua
                             WHERE pua.programid = :{$sqlprefix}certifid
                               AND pua.userid = u.id)";
            $params[$sqlprefix . 'certifid'] = $certifid;
        }

        $sql = implode(' AND ', $sqlchunks);

        return [$sql, $params];
    }

    /**
     * Get the unassigned sql snippet
     *
     * @return array
     */
    private function get_sql_snippet_unassigned() {
        global $DB;

        list($sqlin, $params) = $DB->get_in_or_equal($this->listofids, SQL_PARAMS_NAMED, 'na_'.$this->ruleid);

        $sql = "NOT EXISTS (SELECT 1
                              FROM {prog_user_assignment} pua
                             WHERE pua.programid {$sqlin}
                               AND pua.userid = u.id)";

        return [$sql, $params];
    }
}
