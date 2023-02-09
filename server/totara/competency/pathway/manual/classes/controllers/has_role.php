<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace pathway_manual\controllers;

use moodle_exception;
use pathway_manual\models\roles;
use pathway_manual\models\roles\role;
use totara_core\advanced_feature;

trait has_role {

    /**
     * The role specified in the URL param.
     *
     * @var role|null
     */
    private $role;

    /**
     * Require that the user has the specified role
     *
     * @param role $role
     * @throws \required_capability_exception|\moodle_exception
     */
    protected function require_role(role $role) {
        $role::require_capability($this->user->id);
        $role::require_for_user($this->user->id);
    }

    /**
     * Get all the roles available.
     *
     * @return role[]
     */
    abstract protected function get_roles(): array;

    /**
     * Throw an exception because the current user doesn't have the required roles to view this page.
     *
     * @throws moodle_exception
     */
    abstract protected function user_lacks_role();

    /**
     * Validate that the logged in user has permission to view this page.
     *
     * @return void
     */
    protected function authorize(): void {
        parent::authorize();
        advanced_feature::require('competency_assignment');

        $role_param = optional_param('role', null, PARAM_ALPHANUMEXT);
        if ($role_param) {
            $specified_role = roles\role_factory::create($role_param);
            $this->require_role($specified_role);
            $this->role = $specified_role;
        } else {
            $roles = $this->get_roles();

            if (empty($roles)) {
                $this->user_lacks_role();
            }
        }
    }

}
