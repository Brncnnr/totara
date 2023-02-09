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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\hook;

use mod_perform\hook\dto\pre_deleted_dto;
use mod_perform\hook\dto\pre_deleted_warning_dto;

trait pre_delete_helper {

    /** @var bool $can_delete */
    private $can_delete = true;

    /** @var pre_deleted_dto[] $reasons */
    private $reasons = [];

    /** @var pre_deleted_dto[] $warnings */
    private $warnings = [];

    /**
     * Add can not delete reason
     *
     * @param string $key
     * @param string $description
     * @param array $data
     */
    public function add_reason(string $key, string $description, array $data) {
        if ($this->can_delete) {
            $this->can_delete = false;
        }
        $this->reasons[$key] = new pre_deleted_dto($description, $data);
    }

    /**
     * Get can not delete reason
     *
     * @return array|pre_deleted_dto[]
     */
    public function get_reasons(): array {
        return $this->reasons;
    }

    /**
     * Get first reason why it cannot be deleted.
     *
     * @return pre_deleted_dto|null
     */
    public function get_first_reason(): ?pre_deleted_dto {
        $reasons = $this->get_reasons();

        return array_shift($reasons);
    }

    /**
     * If a section element can be deleted
     *
     * @return bool
     */
    public function can_delete() {
        return $this->can_delete;
    }

    /**
     * Add a warning
     *
     * @param string $key
     * @param string $description
     * @param array $items
     */
    public function add_warning(string $key, string $description, array $items): void {
        $this->warnings[] = new pre_deleted_warning_dto($description, $items);
    }

    /**
     * Get warnings
     *
     * @return array|pre_deleted_warning_dto[]
     */
    public function get_warnings(): array {
        return $this->warnings;
    }
}