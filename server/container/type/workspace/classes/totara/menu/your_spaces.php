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

namespace container_workspace\totara\menu;

use container_workspace\workspace;
use totara_core\advanced_feature;
use totara_core\totara\menu\item;

/**
 * Class your_spaces
 *
 * @package container_workspace\totara\menu
 */
final class your_spaces extends item {
    /**
     * @return int|null
     */
    public function get_default_sortorder(): ?int {
        return 100;
    }

    /**
     * @return string
     */
    protected function get_default_url(): string {
        return '/container/type/workspace/index.php';
    }

    /**
     * @return string
     */
    protected function get_default_title(): string {
        return get_string('your_spaces', 'container_workspace');
    }

    /**
     * @return string
     */
    protected function get_default_parent(): string {
        return '\container_workspace\totara\menu\collaborate';
    }

    /**
     * @return bool|void
     */
    protected function check_visibility(): bool {
        global $USER;
        if (!isloggedin() or isguestuser()) {
            return false;
        }

        if (!advanced_feature::is_enabled('container_workspace')) {
            return false;
        }

        // Must have the view capability
        $context = \context_user::instance($USER->id);
        return has_capability('container/workspace:workspacesview', $context, $USER->id);
    }
}