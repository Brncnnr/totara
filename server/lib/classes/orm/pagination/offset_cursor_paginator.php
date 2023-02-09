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
 * @group orm
 */

namespace core\orm\pagination;

use coding_exception;
use core\orm\entity\repository;
use core\orm\query\builder;
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
 * $query = user::repository()
 *     ->order_by('name')
 *     ->order_by('id');
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
     * @param builder|repository $query
     * @param bool $include_total
     * @return base_cursor|null
     */
    protected function process_query($query, bool $include_total): ?base_cursor {
        if (!$query instanceof builder && !$query instanceof repository) {
            throw new coding_exception('Expected either a builder or a repository object');
        }

        $page = $this->cursor->get_page();
        $limit = $this->cursor->get_limit();

        $builder_paginator = $query->paginate($page, $limit);

        if ($include_total) {
            $this->total = $builder_paginator->get_total();
        }

        $this->items = $builder_paginator->get_items();

        return $this->create_next_cursor($page, $limit);
    }

}