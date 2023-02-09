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
 * Cursor encoding the page and limit for classic offset based pagination
 */
class offset_cursor extends base_cursor {

    /**
     * Set the default cursor data
     *
     * @var array
     */
    protected $cursor = [
        'limit' => offset_cursor_paginator::DEFAULT_ITEMS_PER_PAGE,
        'page' => 1
    ];

    /**
     * @inheritDoc
     */
    protected function validate(array $cursor): void {
        parent::validate($cursor);

        if (!isset($cursor['page']) || !is_numeric($cursor['page']) || $cursor['page'] < 0) {
            throw new coding_exception('You must provide a positive page number within your cursor.');
        }
    }

    /**
     * Returns the page encoded in this cursor
     *
     * @return int
    */
    public function get_page(): int {
        return $this->cursor['page'];
    }

    /**
     * Sets the page which should be encoded in the cursor
     *
     * @param int $page
     * @return $this
     */
    public function set_page(int $page) {
        if (!($page >= 0)) {
            throw new coding_exception('Page has to be a positive integer');
        }
        $this->cursor['page'] = $page;
        return $this;
    }

}