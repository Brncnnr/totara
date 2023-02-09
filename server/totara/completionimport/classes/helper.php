<?php
/*
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_completionimport
 */

namespace totara_completionimport;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Gets the list of users who imported completion records for the given time.
     *
     * @param string $importname course or certification
     * @param int $importtime time of the import
     * @return array
     */
    public static function get_list_of_import_users(string $importname, int $importtime) :array {
        global $DB;

        if ($importname === 'course') {
            $table = 'totara_compl_import_course';
        } else if ($importname === 'certification') {
            $table = 'totara_compl_import_cert';
        } else {
            debugging("Failed to get_list_of_import_users for importname: " . $importname, DEBUG_DEVELOPER);
            return [];
        }

        list($sqlwhere, $sqlparams) = self::get_importsqlwhere($importtime, '');
        $sql = "SELECT *
            FROM {user}
            WHERE id IN (
                SELECT DISTINCT importuserid
                FROM {{$table}}
                {$sqlwhere} AND processed = 0
            )";

        return $DB->get_records_sql($sql, $sqlparams);
    }

    /**
     * Returns the standard filter for the import table and related parameters
     *
     * @param int $importtime time() of the import
     * @param string $alias alias to use
     * @return array array($sql, $params)
     */
    public static function get_importsqlwhere($importtime, $alias = 'i.') :array {
        $sql = "WHERE {$alias}timecreated = :timecreated
            AND {$alias}importerror = 0
            AND {$alias}timeupdated = 0
            AND {$alias}importevidence = 0 ";
        $params = array('timecreated' => $importtime);

        return [$sql, $params];
    }
}