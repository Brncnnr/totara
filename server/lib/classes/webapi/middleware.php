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

use Closure;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * Interface for a GraphQL middleware. Implement this for reusable bits of code which
 * can be used in a flexible way for GraphQL queries and mutations.
 *
 * Optionally add a constructor and make the middleware based on this interface configurable.
 *
 * @package core\webapi
 */
interface middleware {

    /**
     * Handles this middleware and at the end do call the $next Closure
     * to continue down the middleware chain.
     *
     * Example:
     * ```php
     * public function handle(payload $payload, Closure $next): result {
     *     // do whatever the middleware should do
     *
     *     // call the next one on the chain
     *     return $next($payload);
     * }
     * ```
     *
     * For allowing modifications after the resolving:
     * ```php
     * public function handle(payload $payload, Closure $next): result {
     *     // First call the next on the chain
     *     $result = $next($payload);
     *
     *     // do whatever the middleware should do
     *
     *     // and return the result
     *     return $result;
     * }
     * ```
     *
     * @param payload $payload
     * @param Closure $next
     * @return result
     */
    public function handle(payload $payload, Closure $next): result;

}