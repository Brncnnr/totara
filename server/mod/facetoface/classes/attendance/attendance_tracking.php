<?php
/*
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\attendance;
defined('MOODLE_INTERNAL') || die();

/**
 * The interface is for generating the content, and obviously it is for quite a few different contents, it could be a downloadable
 * content, interactive contents or just read only content. If the attendance tracking needs a new type of contents, this interface
 * should be used to extend the functionality.
 *
 * Interface attendance_tracking
 *
 * @package mod_facetoface\attendance
 */
interface attendance_tracking {
    /**
     * Generating a custom form for taking attendance (in other name 'attendance tracking').
     * @return string
     */
    public function generate_content(): string;

    /**
     * Set up the current sessiondate id, so that the attendance tracking is able to render
     * either event tracking or session tracking.
     *
     * @param int $sessiondateid
     * @return object|mixed
     */
    public function set_sessiondate_id(int $sessiondateid);
}