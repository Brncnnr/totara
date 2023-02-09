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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package container_perform
 */

namespace container_perform\watcher;

use container_perform\perform;
use core\notification;
use core_container\hook\base_redirect;
use mod_perform\controllers\activity\edit_activity;
use mod_perform\models\activity\activity;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_url;

class course {

    /**
     * Redirect this page back to the activity edit page (if has permission) with an error message.
     *
     * @param base_redirect $hook
     */
    public static function redirect_with_error(base_redirect $hook): void {
        $container = $hook->get_container();
        if (!$container->is_typeof(perform::get_type())) {
            return;
        }

        notification::error('The specified page is not supported by performance activities.');

        $activity = activity::load_by_container_id($container->get_id());
        if ($activity->can_manage()) {
            redirect(edit_activity::get_url(['activity_id' => $activity->id]));
        } else {
            redirect(new moodle_url('/'));
        }
    }

    /**
     * Show the page, but remove the course navigation and settings blocks.
     *
     * @param base_redirect $hook
     */
    public static function remove_nav_breadcrumbs(base_redirect $hook): void {
        global $PAGE;

        $container = $hook->get_container();
        if (!$container->is_typeof(perform::get_type())) {
            return;
        }

        // Context hasn't actually been set yet. Need to do this before removing the navigation nodes.
        $PAGE->set_context($container->get_context());

        override_nav_breadcrumbs::remove_nav_breadcrumbs($PAGE);
    }

}
