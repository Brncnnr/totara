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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\hook\dto;

use coding_exception;

class pre_deleted_dto {

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string[]
     */
    protected $data;

    public function __construct(string $description, array $data) {
        $this->description = $description;
        $this->set_data($data);
    }

    /**
     * Set activity sections data
     *
     * @param array $data
     * @throws coding_exception
     */
    private function set_data(array $data) {
        foreach ($data as $item) {
            if (!is_string($item)) {
                throw new coding_exception('only strings allowed');
            }
        }

        $this->data = $data;
    }

    /**
     * Get activity sections data
     *
     * @return string[]
     */
    public function get_data(): array {
        return $this->data;
    }

    /**
     * Get can not delete description
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }
}