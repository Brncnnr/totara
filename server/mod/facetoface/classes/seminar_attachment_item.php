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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for a session attachment item that exposes getter methods.
 */
interface seminar_attachment_item {
    /**
     * Get the ID of the item.
     * @return integer
     */
    public function get_id() : int;

    /**
     * Get the name of the item.
     * @return string
     */
    public function get_name() : string;

    /**
     * Get the description of the item.
     * @return string
     */
    public function get_description() : string;

    /**
     * Get the ID of the user who created the item.
     * @return integer
     */
    public function get_usercreated() : int;

    /**
     * Get the ID of the user who last modified the item.
     * @return integer
     */
    public function get_usermodified() : int;

    /**
     * Get the time the item was created.
     * @return integer
     */
    public function get_timecreated() : int;

    /**
     * Get the time the item was last modified.
     * @return integer
     */
    public function get_timemodified() : int;

    /**
     * Get whether the item is custom.
     * @return boolean
     */
    public function get_custom() : bool;

    /**
     * Get whether the item is hidden.
     * @return boolean
     */
    public function get_hidden() : bool;
}
