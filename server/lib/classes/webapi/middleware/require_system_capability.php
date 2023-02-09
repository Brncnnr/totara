<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * Interceptor to confirm if a user has access to the requested data.
 */
class require_system_capability implements middleware {

    /** @var string */
    protected $capability;

    /**
     * require_view_capability constructor.
     *
     * @param string $capability
     */
    public function __construct(string $capability) {
        $this->capability = $capability;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        require_capability($this->capability, \context_system::instance());

        return $next($payload);
    }
}
