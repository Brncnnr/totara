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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query\event\sortorder;

use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Retrieving a sort order SQL statement for seminar_event. This class will be primarily used in \mod_facetoface\query\query,
 * and its method will be invoked in function ::get_event_sql_and_params.
 */
abstract class sortorder {
    /**
     * Returning sort sql for seminar_event list. With a key word SORT ORDER at first, so that when we read at the value return,
     * it makes more sense.
     *
     * Context of parent SQL, that the returned SQL of this function will be a part of.
     * s.* => for {facetoface_sessions} table,
     * m. => [
     *  . sessionid => {facetoface_sessions}.id
     *  . mintimestart => MIN({facetoface_sessions_dates}.timestart)
     *  . maxtimefinish => MAX({facetoface_sessions_dates}.timefinish)
     * ] for second inner query, built up a temporary table.
     *
     * @return string
     */
    abstract public function get_sort_sql(): string;

    /**
     * Apply sorting order to the current query builder.
     * @param builder $builder
     * @return void
     */
    abstract public function apply(builder $builder): void;
}