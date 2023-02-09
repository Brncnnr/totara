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
 * @package core
 */
namespace core\json_editor\node\abstraction;

use core\json_editor\node\attribute\extra_linked_file;

/**
 * An interface to tell whether the node has extra linked file or not.
 */
interface has_extra_linked_file {
    /**
     * Returning null means that the node does not have extra linked file added
     * yet. Otherwise the extra linked file is appearing within the node itself.
     *
     * @return extra_linked_file|null
     */
    public function get_extra_linked_file(): ?extra_linked_file;
}