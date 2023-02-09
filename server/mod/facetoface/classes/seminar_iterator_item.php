<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface seminar_iterator_item
 *
 * This needs to be implemented by all items that can be handled by a Seminar Iterator class.
 * These methods are used internally by Seminar Iterators to manage their lists.
 */
interface seminar_iterator_item {
    /**
     * Returns the ID for the item.
     * @return int
     */
    public function get_id(): int;

    /**
     * Deletes the item.
     *
     * This should remove any database records belonging to the item.
     *
     * @return mixed
     */
    public function delete();
}