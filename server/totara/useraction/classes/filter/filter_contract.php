<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\filter;

use core\entity\user_repository;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * The base contract that each individual action must implement.
 */
interface filter_contract {
    /**
     * Create from posted input.
     *
     * @param mixed $input
     * @return static
     */
    public static function create_from_input($input): self;

    /**
     * Create from stored values.
     *
     * @param mixed $stored
     * @return static
     */
    public static function create_from_stored($stored): self;

    /**
     * Convert the internally stored record into an array of entries.
     *
     * @return mixed
     */
    public function to_graphql();

    /**
     * Apply filter to a user repository.
     *
     * @param user_repository $user_repository
     * @param execution_data $execution_data
     * @return user_repository
     */
    public function apply(user_repository $user_repository, execution_data $execution_data): user_repository;
}
