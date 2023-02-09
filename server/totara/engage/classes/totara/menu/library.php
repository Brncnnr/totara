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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\totara\menu;

use totara_core\advanced_feature;

class library extends \totara_core\totara\menu\item {

    public function get_default_sortorder() {
        return 30000;
    }

    public function get_incompatible_preset_rules(): array {
        return ['can_view_engage'];
    }

    /**
     * @return bool
     */
    public function is_disabled() {
        return advanced_feature::is_disabled('engage_resources');
    }

    protected function get_default_title() {
        return get_string('yourlibrary', 'totara_engage');
    }

    protected function get_default_url() {
        return '/totara/engage/your_resources.php';
    }

    protected function check_visibility() {
        global $USER;
        if (!isloggedin() or isguestuser()) {
            return false;
        }

        if (advanced_feature::is_disabled('engage_resources')) {
            return false;
        }

        $context = \context_user::instance($USER->id);
        return has_capability('totara/engage:viewlibrary', $context, $USER->id);
    }

    protected function get_default_parent() {
        return '\totara_core\totara\menu\learn';
    }
}