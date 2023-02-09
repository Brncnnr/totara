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
 * @package mod_perform
 */

namespace mod_perform\totara\menu;

use totara_core\advanced_feature;
use totara_core\totara\menu\item;

class my_activities extends item {
    protected function get_default_title() {
        return get_string('menu_title_my_activities', 'mod_perform');
    }

    protected function get_default_url() {
        return '/mod/perform/activity/index.php';
    }

    public function get_default_sortorder() {
        return 50040;
    }

    protected function check_visibility() {
        return isloggedin()
               && !isguestuser();
    }

    protected function get_default_parent() {
        return '\totara_core\totara\menu\perform';
    }

    public function is_disabled() {
        return advanced_feature::is_disabled('performance_activities');
    }
}
