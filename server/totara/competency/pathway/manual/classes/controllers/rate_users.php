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
 * @package pathway_manual
 */

namespace pathway_manual\controllers;

use context;
use context_system;
use core\entity\user;
use pathway_manual\models\roles;
use pathway_manual\models\roles\role;
use totara_mvc\controller;
use totara_mvc\tui_view;

class rate_users extends controller {

    use has_role;

    protected $url = '/totara/competency/rate_users.php';

    /**
     * @return context_system
     */
    protected function setup_context(): context {
        return context_system::instance();
    }

    /**
     * Set up the page.
     *
     * @return tui_view
     */
    public function action() {
        $page_title = get_string('rate_competencies', 'pathway_manual');
        $this->get_page()->navbar->add($page_title);

        $vue_props = [
            'current-user-id' => user::logged_in()->id,
            'toast-message' => rate_competencies::get_toast_message_from_url(),
        ];

        if ($this->role) {
            $vue_props['specified-role'] = $this->role::get_name();
        }

        return tui_view::create('pathway_manual/pages/RateUsers', $vue_props)
            ->set_title($page_title);
    }

    /**
     * Require that the user has the specified role
     *
     * @param role $role
     * @throws \moodle_exception
     */
    protected function require_role(role $role) {
        if (!$role::has_for_any()) {
            $this->user_lacks_role();
        }
    }

    /**
     * Get all the roles available.
     *
     * @return role[]
     */
    protected function get_roles(): array {
        return roles::get_current_user_roles_for_any();
    }

    /**
     * Throw an exception because the current user doesn't have the required roles to view this page.
     *
     * @throws \moodle_exception
     */
    protected function user_lacks_role() {
        print_error('error_user_lacks_role_for_any', 'pathway_manual');
    }

}
