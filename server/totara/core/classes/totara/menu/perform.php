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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\totara\menu;

use totara_core\advanced_feature;
use totara_core\totara\menu\container;

class perform extends container {
    protected function get_default_title() {
        return get_string('menu_title_perform', 'totara_core');
    }

    public function get_default_sortorder() {
        return 50000;
    }

    protected function check_visibility() {
        return isloggedin()
               && !isguestuser();
    }

    public function is_disabled() {
        // TODO should detect if the perform flavor is enabled but there is no
        // such setting as yet. So just always enable the menu item for now.
        return false;
    }
}
