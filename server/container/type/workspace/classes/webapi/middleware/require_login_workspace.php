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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * Use this middleware if your request contains a workspace id argument.
 * This middleware will automatically try to resolve the workspace and set
 * the correct parameters for require login.
 *
 * If the workspace id is not correctly set, means empty or not an existing workspace an exception will be thrown.
 */
class require_login_workspace implements middleware {

    /**
     * @var string
     */
    protected $workspace_id_argument_name;

    protected $auto_login_guest = false;

    /**
     * @param string $workspace_id_argument_name the argument name for the workspace id
     * @param bool $auto_login_guest
     */
    public function __construct(string $workspace_id_argument_name, bool $auto_login_guest = false) {
        $this->workspace_id_argument_name = $workspace_id_argument_name;
        $this->auto_login_guest = $auto_login_guest;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        $workspace_id = $payload->get_variable($this->workspace_id_argument_name);
        if (empty($workspace_id)) {
            throw new \moodle_exception('invalid_workspace', 'container_workspace');
        }

        try {
            $workspace = get_course($workspace_id);
        } catch (\Exception $exception) {
            throw new \moodle_exception('invalid_workspace', 'container_workspace');
        }

        // Always prevent redirects for GraphQL requests
        // and we do not need to set the wantsurl to the current url
        \require_login($workspace, $this->auto_login_guest, null, false, true);

        return $next($payload);
    }

}