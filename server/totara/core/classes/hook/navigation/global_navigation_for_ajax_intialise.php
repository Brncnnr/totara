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

class global_navigation_for_ajax_intialise extends base {
    /**
     * @var \global_navigation_for_ajax
     */
    private $nav;

    /**
     * Don't let the kids mess up the parent's construction !
     *
     * base_global_navigation constructor.
     * @param \global_navigation_for_ajax $nav
     */
    final public function __construct(\global_navigation_for_ajax $nav) {
        parent::__construct();
        $this->nav = $nav;
    }

    /**
     * @return \global_navigation_for_ajax
     */
    public function get_navigation(): \global_navigation_for_ajax {
        return $this->nav;
    }
}