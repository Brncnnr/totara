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
 * @package totara_competency
 */

namespace totara_competency\totara\menu;

use totara_competency\helpers\capability_helper;
use totara_core\advanced_feature;
use totara_core\totara\menu\item;

class my_competencies extends item {
    protected function get_default_title() {
        return get_string('menu_title_my_competencies', 'totara_competency');
    }

    protected function get_default_url() {
        return '/totara/competency/profile/index.php';
    }

    public function get_default_sortorder() {
        return 50030;
    }

    protected function check_visibility() {
        global $USER;

        return isloggedin()
               && !isguestuser()
               && capability_helper::can_view_profile($USER->id);
    }

    protected function get_default_parent() {
        return '\totara_core\totara\menu\perform';
    }

    public function is_disabled() {
        return advanced_feature::is_disabled('competency_assignment');
    }
}
