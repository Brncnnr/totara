<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package container_approval
 */

namespace container_approval\watcher;

use container_approval\approval;
use core_container\hook\base_redirect;

class course {

    /**
     * Redirect this page back to the activity edit page (if has permission) with an error message.
     *
     * @param base_redirect $hook
     */
    public static function redirect_to_workflow(base_redirect $hook): void {
        $type = approval::get_type();
        $container = $hook->get_container();

        if (!$container->is_typeof($type)) {
            return;
        }

        redirect($container->get_view_url());
    }

    /**
     * Show the page, but remove the course navigation and settings blocks.
     *
     * @param base_redirect $hook
     */
    public static function remove_nav_breadcrumbs(base_redirect $hook): void {
        global $PAGE;

        $container = $hook->get_container();
        if (!$container->is_typeof(approval::get_type())) {
            return;
        }

        // Context hasn't actually been set yet. Need to do this before removing the navigation nodes.
        $PAGE->set_context($container->get_context());

        // Remove course-related settings blocks.
        $settings_navigation = $PAGE->settingsnav;
        $settings_navigation->children->remove('categorysettings');
        $settings_navigation->children->remove('modulesettings');
        $settings_navigation->children->remove('courseadmin');

        // Remove course related breadcrumbs.
        $breadcrumbs = $PAGE->navigation->children;
        $breadcrumbs->remove('courses');
    }

}
