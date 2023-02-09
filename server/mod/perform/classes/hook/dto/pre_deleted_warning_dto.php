<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\hook\dto;

use coding_exception;
use moodle_url;

/**
 * Data transfer object wrapping a pre-deletion warning message.
 */
class pre_deleted_warning_dto {

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string[]
     */
    protected $items = [];

    public function __construct(string $description, array $items) {
        $this->description = $description;
        $this->set_items($items);
    }

    /**
     * Set warning items (e.g. activity sections).
     *
     * @param array $warning_items
     * @throws coding_exception
     */
    private function set_items(array $warning_items): void {
        foreach ($warning_items as $warning_item) {
            if (!is_array($warning_item)) {
                throw new coding_exception('only arrays allowed');
            }
            if (!is_string($warning_item['item'])) {
                throw new coding_exception('only strings allowed');
            }
            if (!empty($warning_item['url']) && !($warning_item['url'] instanceof moodle_url)) {
                throw new coding_exception('only moodle_url allowed');
            }
        }

        $this->items = $warning_items;
    }

    /**
     * Get warning items
     *
     * @return string[]
     */
    public function get_items(): array {
        return $this->items;
    }

    /**
     * Get warning description
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }
}