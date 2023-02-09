<?php
/*
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
 */

namespace core\webapi;

/**
 * Interface for a GraphQL middleware group.
 *
 * @package core\webapi
 */
interface middleware_group {

    /**
     * Returns an array of middleware which should be applied for this group
     *
     * Example:
     * ```php
     * public function get_middleware(): array {
     *     return [
     *         middleware1::class,
     *         middleware2::class
     *     ];
     * }
     * ```
     *
     * @return middleware[]
     */
    public function get_middleware(): array;

}