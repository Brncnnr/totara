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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\totara\menu;

use totara_core\advanced_feature;
use totara_core\totara\menu\item;

/**
 * Application dashboard in the menu.
 */
class dashboard extends item {
    protected function get_default_title() {
        return get_string('menu_application', 'mod_approval');
    }

    protected function get_default_url() {
        return '/mod/approval/application/index.php';
    }

    public function get_default_sortorder() {
        return 54321;
    }

    protected function check_visibility() {
        return isloggedin() && !isguestuser() && advanced_feature::is_enabled('approval_workflows');
    }

    protected function get_default_parent() {
        return '\mod_approval\totara\menu\root';
    }
}
