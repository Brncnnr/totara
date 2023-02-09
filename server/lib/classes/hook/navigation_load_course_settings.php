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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

namespace core\hook;

/**
 * Hook for allowing manipulation of the course navigation node post loading.
 *
 * Use the $hook->get_node() method to acquire a reference to the course node.
 */
class navigation_load_course_settings extends \totara_core\hook\base {

    /**
     * The course node.
     * @var \navigation_node
     */
    private $node;

    /**
     * The course object.
     * @var \stdClass
     */
    private $course;

    /**
     * @param \navigation_node $node The course node.
     */
    public function __construct(\navigation_node $node, \stdClass $course) {
        $this->node = $node;
        $this->course = $course;
    }

    /**
     * Returns the course node.
     * @return \navigation_node
     */
    public function get_node(): \navigation_node {
        return $this->node;
    }

    /**
     * Returns the course object.
     * @return \stdClass
     */
    public function get_course(): \stdClass {
        return $this->course;
    }

}
