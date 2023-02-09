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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_hierarchy
 */

namespace hierarchy_competency\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;


defined('MOODLE_INTERNAL') || die();

/**
 * Handler for the tracking of a user's progress towards achieving a competency.
 * @deprecated since Totara 13
 */
class competency_progress extends item {

    // To allow the userdata unit tests to succeed, we can't output the deprecation message at the top if as all userdata
    // class files are imported during the tests.
    private static function is_deprecated() {
        debugging('hierarchy_competency\userdata\competency_evidence has been deprecated, please use totara_competency\userdata\achievement instead.', DEBUG_DEVELOPER);
    }

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     */
    public static function get_main_component() {
        return 'totara_competency';
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 2; // 3rd item of 6 in the 'Competencies' list.
    }

    /**
     * {@inheritDoc}
     */
    public static function is_purgeable(int $userstatus) {
        return false;
    }


    /**
     * {@inheritDoc}
     */
    protected static function purge(target_user $user, \context $unused) {
        global $DB;

        static::is_deprecated();
        $params = ['userid' => $user->get_user_record()->id];
        $DB->delete_records('comp_criteria_record', $params);

        return self::RESULT_STATUS_SUCCESS;
    }


    /**
     * {@inheritDoc}
     */
    public static function is_exportable() {
        return false;
    }


    /**
     * {@inheritDoc}
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        static::is_deprecated();
        $params = ['userid' => $user->get_user_record()->id];
        $filter = "
            SELECT c.shortname, cc.itemtype, ccr.timecreated
              FROM {comp_criteria_record} ccr
              JOIN {comp_criteria} cc ON ccr.itemid = cc.id
              JOIN {comp} c ON ccr.competencyid = c.id
             WHERE ccr.userid = :userid
        ";

        $export = new export();
        foreach ($DB->get_records_sql($filter, $params) as $competency) {
            $export->data[] = [
                'competency' => $competency->shortname,
                'criteria' => $competency->itemtype,
                'created on' => $competency->timecreated
            ];
        }

        return $export;
    }


    /**
     * {@inheritDoc}
     */
    public static function is_countable() {
        return false;
    }


    /**
     * {@inheritDoc}
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        static::is_deprecated();
        $params = ['userid' => $user->get_user_record()->id];
        return $DB->count_records('comp_criteria_record', $params);
    }
}
