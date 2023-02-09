<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\performance_statistics;

use stdClass;

/**
 * Returns information about how many database reads and writes happened
 * and how much total time database queries took.
 *
 * @package core\performance_statistics
 */
class db extends provider {

    /**
     * @inheritDoc
     */
    public function get_data() {
        global $DB, $PERF;

        $data = new stdClass();
        $data->reads = $DB->perf_get_reads();
        $data->writes = ($DB->perf_get_writes() - $PERF->logwrites);
        $data->time = round($DB->perf_get_queries_time(), 5);

        return $data;
    }

}