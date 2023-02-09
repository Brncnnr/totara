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

namespace core\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * This middleware handles require_login. This is a basic version which does assume
 * that no course needs to be set. The only configuration option is auto_login_guest.
 *
 * Use the @see \core\webapi\middleware\require_login_course middleware if a course is required for the resolver.
 *
 * Some flags which do not make sense for GraphQL are forced, like preventredirect and wantsurltome
 */
class require_login implements middleware {

    protected $auto_login_guest = false;

    /**
     * @param bool $auto_login_guest
     */
    public function __construct(bool $auto_login_guest = false) {
        $this->auto_login_guest = $auto_login_guest;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        // Always prevent redirects for GraphQL requests
        // and we do not need to set the wantsurl to the current url
        \require_login(null, $this->auto_login_guest, null, false, true);

        return $next($payload);
    }

}