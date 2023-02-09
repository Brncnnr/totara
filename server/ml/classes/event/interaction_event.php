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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_ml
 */
namespace core_ml\event;

/**
 * Interface for interaction event
 */
interface interaction_event {
    /**
     * Returning the component where the interaction is happening.
     * @return string
     */
    public function get_component(): string;

    /**
     * @return string|null
     */
    public function get_area(): ?string;

    /**
     * @return string
     */
    public function get_interaction_type(): string;

    /**
     * @return int
     */
    public function get_rating(): int;

    /**
     * @return int
     */
    public function get_user_id(): int;

    /**
     * @return int
     */
    public function get_item_id(): int;
}