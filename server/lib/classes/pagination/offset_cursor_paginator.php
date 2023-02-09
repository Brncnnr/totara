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
 * @group paginator
 */

namespace core\pagination;

use coding_exception;

/**
 * This paginator is used for cursor based offset pagination
 *
 * The query going in here should be ordered and the order should be predictable.
 * If you order by a column which can have duplicates content in it add a second order
 * over a unique and sequential column to make sure if gives a consistent result across pages.
 */
abstract class offset_cursor_paginator extends base_cursor_paginator {

    /**
     * @param mixed $query
     * @param offset_cursor $cursor
     */
    public function __construct($query, $cursor = null) {
        // Offset paginator without total does not make sense, so let's force it
        parent::__construct($query, $cursor, true);
    }

    /**
     * @inheritDoc
     */
    protected static function normalise_cursor($cursor): base_cursor {
        if (is_null($cursor)) {
            $cursor = new offset_cursor();
        }
        if (is_string($cursor)) {
            $cursor = offset_cursor::decode($cursor);
        }
        if (!$cursor instanceof offset_cursor) {
            throw new coding_exception('Expected either null, encoded cursor or cursor object');
        }

        // Set page 1 as default if the page is not defined
        if ($cursor->get_page() === null) {
            $cursor->set_page(1);
        }
        if ($cursor->get_limit() === null) {
            $cursor->set_limit(static::DEFAULT_ITEMS_PER_PAGE);
        }

        return $cursor;
    }

    /**
     * @inheritDoc
     */
    protected function validate_cursor($query, base_cursor $cursor): void {
        // Page zero means unlimited fetch.
        if (!($cursor->get_page() >= 0)) {
            throw new coding_exception('The cursor needs a page to be set, 0 or greater');
        }

        // Or limit zero also means unlimited fetch.
        if (!($cursor->get_limit() >= 0)) {
            throw new coding_exception('The cursor needs a limit to be set, 0 or greater');
        }
    }

    /**
     * Create new next cursor based on the current page we are in or return null
     * if we reached the end of the results
     *
     * @param int $page
     * @param int $limit
     * @return base_cursor|null
     */
    protected function create_next_cursor(int $page, int $limit): ?base_cursor {
        $next_cursor = null;

        // If page is zero, means that it is unlimited fetch - hence there is no point to do the
        // calculation for the next cursor.
        if ($limit > 0 && $page > 0) {
            $last_page = ceil($this->total / $limit);
            if ($page < $last_page) {
                $next_cursor = offset_cursor::create()
                    ->set_page($page + 1)
                    ->set_limit($limit);
            }
        }

        return $next_cursor;
    }

}