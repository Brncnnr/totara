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

namespace totara_engage\sidepanel;

abstract class provider {

    /**
     * Indicate whether or not the provider has any component to inject into
     * the navigation panel.
     *
     * @return bool
     */
    public static function provide_navigation_section(): bool {
        return false;
    }

    /**
     * Get the component to inject into the navigation panel.
     *
     * @return array
     */
    abstract function get_navigation_section(): array;

    /**
     * @return bool
     */
    abstract function show_create_button(): bool;

}