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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\watcher;

use container_workspace\workspace;
use core_container\hook\base_redirect;

/**
 * Watcher class for redirecting to workspace
 */
final class redirect_watcher {
    /**
     * @param base_redirect $hook
     * @return void
     */
    public static function redirect_to_workspace(base_redirect $hook): void {
        $type = workspace::get_type();
        $container = $hook->get_container();

        if (!$container->is_typeof($type)) {
            return;
        }

        redirect($container->get_view_url());
    }
}