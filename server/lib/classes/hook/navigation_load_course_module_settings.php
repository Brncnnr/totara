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
 * Hook for allowing manipulation of the course module navigation node post loading.
 *
 * Use the $hook->get_node() method to acquire a reference to the module node.
 */
class navigation_load_course_module_settings extends \totara_core\hook\base {

    /**
     * The course module node.
     * @var \navigation_node
     */
    private $node;

    /**
     * The course module we are loading for.
     * @var \cm_info
     */
    private $cm;

    /**
     * @param \navigation_node $node The course module node.
     */
    public function __construct(\navigation_node $node, \cm_info $cm) {
        $this->node = $node;
        $this->cm = $cm;
    }

    /**
     * Returns the course module node.
     * @return \navigation_node
     */
    public function get_node(): \navigation_node {
        return $this->node;
    }

    /**
     * Returns the course module info object we are loading for.
     * @return \cm_info
     */
    public function get_cm(): \cm_info {
        return $this->cm;
    }

}
