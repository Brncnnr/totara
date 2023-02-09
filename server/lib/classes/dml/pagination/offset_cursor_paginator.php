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
 * @group dml
 */

namespace core\dml\pagination;

use core\collection;
use core\dml\sql;
use core\pagination\base_cursor;
use core\pagination\offset_cursor_paginator as core_offset_cursor_paginator;

/**
 * This paginator is used for cursor based offset pagination
 *
 * Usage example:
 * ```php
 * // Cursor string is the base64 encoded json string containing the limit and page
 * $cursor = null;
 * if (!empty($cursor_string)) {
 *     $cursor = offset_cursor::decode($cursor_string);
 * }
 *
 * $query = new sql('SELECT * FROM {user} ORDER BY name, id');
 *
 * $paginator = new offset_cursor_paginator($query, $cursor);
 *
 * // Returns an array containing the items, the next cursor and total amount
 * $result = $paginator->get();
 *
 * // You can also iterate over the paginator
 * foreach ($paginator as $item) {
 *     echo $item->fullname;
 * }
 * ```
 *
 * The query going in here should be ordered and the order should be predictable.
 * If you order by a column which can have duplicates content in it add a second order
 * over a unique and sequential column to make sure if gives a consistent result across pages.
 */
final class offset_cursor_paginator extends core_offset_cursor_paginator {
    /**
     * Reformat to set limit to zero and page to one if the page is zero.
     *
     * @param base_cursor|string|null $cursor
     * @return base_cursor
     */
    protected static function normalise_cursor($cursor): base_cursor {
        $cursor_instance = parent::normalise_cursor($cursor);
        $page = $cursor_instance->get_page();

        if (0 === $page) {
            // Page zero also means that the we are going to fetch unlimited.
            // Reset it to limit 0 and page to 1 in order to allow fetching works.
            $cursor_instance->set_page(1);
            $cursor_instance->set_limit(0);
        }

        return $cursor_instance;
    }

    /**
     * @param sql $query
     * @param bool $include_total
     * @return base_cursor|null
     */
    protected function process_query($query, bool $include_total): ?base_cursor {
        global $DB;

        if (!$query instanceof sql) {
            throw new \coding_exception('Expected a \core\dml\sql object for paginating queries');
        }

        $page = $this->cursor->get_page();
        $limit = $this->cursor->get_limit();

        $offset = ($page == 1 || $page == 0) ? 0 : (($page - 1) * $limit);

        if ($include_total) {
            $cnt_query = $query->get_sql();
            // If there's an ORDER BY in the query we run into issues with MSSQL which
            // does not support ORDER BY in derived tables / subqueries with out an OFFSET
            if ($DB->get_dbvendor() === 'mssql'
                && !preg_match("/OFFSET [0-9]+ ROW/", $cnt_query)
                && !preg_match("/TOP [0-9]+/", $cnt_query)
            ) {
                $cnt_query = $query.' OFFSET 0 ROWS';
            }
            // Wrap the query with a count to support GROUP BY and the sorts
            $count_sql = new sql("SELECT COUNT(*) FROM ({$cnt_query}) ocp_count", $query->get_params());
            $this->total = $DB->count_records_sql($count_sql, null);
        }

        $items = $DB->get_records_sql($query, null, $offset, $limit);

        $this->items = new collection($items);

        return $this->create_next_cursor($page, $limit);
    }

}