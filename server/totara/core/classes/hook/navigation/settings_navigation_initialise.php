<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */
namespace totara_core\hook\navigation;

class settings_navigation_initialise extends base {
    /**
     * @var \settings_navigation
     */
    private $nav;

    /**
     * base_settings_navigation constructor.
     * @param \settings_navigation $nav
     */
    public function __construct(\settings_navigation $nav) {
        parent::__construct();
        $this->nav = $nav;
    }

    /**
     * @return \settings_navigation
     */
    public function get_navigation(): \settings_navigation {
        return $this->nav;
    }
}