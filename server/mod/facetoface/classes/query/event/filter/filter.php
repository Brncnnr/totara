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

namespace mod_facetoface\query\event\filter;

use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * This filter class will be explicitly used in seminar_event_query class, as playing a role that build a SQL query, to filter out
 * the records that does not meet criteria defined at child class.
 */
abstract class filter {
    /**
     * Each filter should have a unique name or given name at external, so that the query object itself
     * is able to detect whether the filter has already been applied or not.
     *
     * @var string
     */
    protected $name;

    /**
     * filter constructor.
     *
     * @param string $name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Returning an array of sql and parameters, where sql at first index and parameters after.
     * The context (fields and tables) for this method to constructing a part of SQL here.
     * + s.* =>  for {facetoface_sessions} table
     * + m.* => [
     *  . sessionid => {facetoface_sessions}.id
     *  . mintimestart => MIN({facetoface_sessions_dates}.timestart)
     *  . maxtimefinish => MAX({facetoface_sessions_dates}.timefinish)
     * ] for second inner query, built up a temporary table
     *
     * Note that do not add a keyword `AND` in front of the part SQL statement returned. And by default, the parameters retrieved
     * via this method should associated with a name. Otherwise, $DB will throw exception, on failure on parsing parameters.
     *
     * Always be sure that the sql return has something like '1=1', because empty string will fail the sql builder.
     *
     * @param integer $time The current timestamp.
     *
     * @return array
     */
    abstract public function get_where_and_params(int $time): array;

    /**
     * Apply filter to the current query builder.
     *
     * @param builder $builder
     * @param integer $time The current timestamp
     * @return void
     */
    abstract public function apply(builder $builder, int $time): void;
}
