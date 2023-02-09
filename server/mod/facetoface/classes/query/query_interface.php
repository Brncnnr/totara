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

namespace mod_facetoface\query;

defined('MOODLE_INTERNAL') || die();

/**
 * This is just contractor between the retriever and the query builder, as there could be different style of constructing
 * a query with different type of default data. Hence, having one contractor function is good enough, for the external
 * usages to always rely on the statement returned, and use that statement for retrieving data, and run the logic.
 *
 * Query could have different type of filters, sort orders and/or dependencies. This makes it to be quite hard
 * for having one or a few of contract functionalities to handle these kind of dependencies. Therefore, it should be
 * up to developer to implement it at the child level.
 *
 * The purpose of this is to just allow the external usage to get the statement built up from different kind of logics,
 * and execute the statement to build the records needed.
 */
interface query_interface {
    /**
     * Returning an object holding sql and parameters.
     * @return statement
     */
    public function get_statement(): statement;
}