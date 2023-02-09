<?php
/**
 * This file is part of Totara Learn
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\webapi\middleware;

use Closure;
use container_workspace\interactor\workspace\interactor;
use container_workspace\workspace;
use core\entity\user;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use core_container\factory;

/**
 * A middleware to check whether the workspace is valid,
 * and optionally if the user has the correct interactor abilities.
 */
class require_workspace_access implements middleware {
    /**
     * @var string
     */
    private $id_path;

    /**
     * @var string[]
     */
    private $require_abilities;

    public function __construct(string $id_path = 'workspace_id', array $require_abilities = []) {
        $this->id_path = $id_path;
        $this->require_abilities = $require_abilities;
    }

    /**
     * @param payload $payload
     * @param Closure $next
     *
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        // Handle paths, sometimes the input field might be a level deep
        // We only support two levels
        $workspace_id = $this->get_workspace_id($payload, $this->id_path);

        if (empty($workspace_id)) {
            throw new \moodle_exception('invalid_workspace', 'container_workspace');
        }

        /** @var workspace $workspace */
        try {
            $workspace = factory::from_id($workspace_id);
        } catch (\dml_missing_record_exception $ex) {
            $workspace = null;
        }
        if (empty($workspace) || !$workspace->is_typeof(workspace::get_type()) || $workspace->is_to_be_deleted()) {
            // We throw a generic error, we don't need to expose why the workspace isn't valid
            throw new \moodle_exception('invalid_workspace', 'container_workspace');
        }

        // Confirm they have the correct abilities
        if (!empty($this->require_abilities)) {
            $user_id = user::logged_in()->id;

            $interactor = new interactor($workspace, $user_id);
            foreach ($this->require_abilities as $ability) {
                if (!method_exists($interactor, $ability)) {
                    throw new \coding_exception("There is no interactor method '{$ability}'");
                }

                if (call_user_func([$interactor, $ability]) === false) {
                    throw new \moodle_exception('invalid_workspace', 'container_workspace');
                }
            }
        }

        return $next->__invoke($payload);
    }

    /**
     * Returns the workspace id parameter based on the provided path.
     *
     * @param payload $payload
     * @param string $path
     * @return mixed|null
     */
    private function get_workspace_id(payload $payload, string $path) {
        if (strstr($path, '.') !== false) {
            $paths = explode('.', $path, 2);

            if (!$payload->has_variable($paths[0])) {
                throw new \coding_exception(
                    "Cannot find the field '{$path}' in payload"
                );
            }

            $wrapper = $payload->get_variable($paths[0]);
            if (!array_key_exists($paths[1], $wrapper)) {
                throw new \coding_exception(
                    "Cannot find the field '{$path}' in payload"
                );
            }

            return $wrapper[$paths[1]];
        }

        if (!$payload->has_variable($path)) {
            throw new \coding_exception(
                "Cannot find the field '{$path}' in payload"
            );
        }
        return $payload->get_variable($path);
    }


}