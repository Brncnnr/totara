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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
namespace core_container\setting;

/**
 * A setting class that help the container's to provide setting injection
 * for the children.
 */
abstract class setting {
    /**
     * Preventing the children to have complicated construction.
     * setting constructor.
     */
    final public function __construct() {
    }

    /**
     * A callback function to help the plugins/children to inect
     *
     * @param \part_of_admin_tree $admin_root
     * @return void
     */
    abstract public function init(\part_of_admin_tree $admin_root): void;
}