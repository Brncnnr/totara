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

class cursor extends base_cursor {

    /**
     * Set the default cursor data
     *
     * @var array
     */
    protected $cursor = [
        'limit' => cursor_paginator::DEFAULT_ITEMS_PER_PAGE,
        'columns' => null
    ];

    /**
     * @inheritDoc
     */
    protected function validate(array $cursor): void {
        parent::validate($cursor);

        if (!array_key_exists('columns', $cursor) || (!is_array($cursor['columns']) && !is_null($cursor['columns']))) {
            throw new coding_exception('You must provide columns within your cursor.');
        }
    }

    /**
     * Returns the columns encoded in this cursor
     *
     * @return array|null
     */
    public function get_columns(): ?array {
        return $this->cursor['columns'];
    }

    /**
     * Sets the columns which should be encoded in the cursor
     *
     * @param array|null $columns
     * @return $this
     */
    public function set_columns(?array $columns) {
        if (is_array($columns)) {
            foreach (array_keys($columns) as $key) {
                if (!is_string($key)) {
                    throw new coding_exception('Expecting an array with column names as keys');
                }
            }
        }
        $this->cursor['columns'] = $columns;
        return $this;
    }

}