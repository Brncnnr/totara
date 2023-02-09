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
use core\entity\tenant;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * Interceptor to confirm if a user is site administrator.
 */
class require_theme_settings implements middleware {

    protected $tenant_id_argument_name;

    /**
     * @param string $tenant_id_argument_name the argument name for the tenant id
     */
    public function __construct(string $tenant_id_argument_name) {
        $this->tenant_id_argument_name = $tenant_id_argument_name;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        global $CFG;

        // Because appearance is tenant aware we need to check the capability on tenant context level.
        $tenant_id = $payload->get_variable($this->tenant_id_argument_name);
        if ($CFG->tenantsenabled && !empty($tenant_id)) {
            if (!tenant::repository()->find($tenant_id)) {
                throw new \invalid_parameter_exception('Invalid tenant_id');
            }
            $tenant = \core\record\tenant::fetch($tenant_id);
            $context = \context_coursecat::instance($tenant->categoryid);
        } else {
            $context = \context_system::instance();
        }

        require_capability('totara/tui:themesettings', $context);

        return $next($payload);
    }
}