<?php
/*
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\rb\traits;

defined('MOODLE_INTERNAL') || die();

trait deprecated_signin_source {

    /**
     * Display function for signature column.
     *
     * This column is used by reports which generate sign-in sheets
     * (printed PDF exports). The content here increases the space
     * for an attendee to provide a signature. [Unix] newlines are
     * converted to linebreak HTML tags.
     *
     * @deprecated Since Totara 12.0
     * @param $position
     * @param $row
     * @return string
     */
    public function rb_display_signature($position, $row) {
        debugging('rb_source_facetoface_signin::rb_display_signature has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_signature::display', DEBUG_DEVELOPER);
        return "\n\n";
    }

    /**
     * Display function for job.
     *
     * @deprecated Since Totara 12.0
     * @param $jobassignmentid
     * @param $row
     * @return string
     */
    public function rb_display_position_type($jobassignmentid, $row) {
        debugging('rb_source_facetoface_signin::rb_display_position_type has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        // Deprecate - you should probably link to the job table and get the full name (unless we want default lang string names).
        return 'fixme';
    }

    /**
     * Display function for the booking managers name (linked to
     * their profile).
     *
     * @deprecated Since Totara 12.0
     * @param $name
     * @param $row
     * @return string
     */
    function rb_display_link_f2f_bookedby($name, $row) {
        debugging('rb_source_facetoface_signin::rb_display_link_f2f_bookedby has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_booked_by_link::display', DEBUG_DEVELOPER);
        $user = fullname($row);
        return $this->rb_display_link_user($user, $row, false);
    }

    /**
     * Display function for the actioning users name (linked to
     * their profile).
     *
     * @deprecated Since Totara 12.0
     * @param $name
     * @param $row
     * @return string
     */
    function rb_display_link_f2f_actionedby($name, $row) {
        debugging('rb_source_facetoface_signin::rb_display_link_f2f_actionedby has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $user = fullname($row);
        return $this->rb_display_link_user($user, $row, false);
    }

    /**
     * Display function to show 'Reserved' for reserved spaces.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_user($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_signin::rb_display_link_user has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\user_link::display', DEBUG_DEVELOPER);
        if ($row->id) {
            return parent::rb_display_link_user($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_signin');
    }

    /**
     * Display function to link the user icon.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_user_icon($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_signin::rb_display_link_user_icon has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\user_icon_link::display', DEBUG_DEVELOPER);
        if ($row->id) {
            return parent::rb_display_link_user_icon($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_signin');
    }

    /**
     * Display function to show the user.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_user($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_signin::rb_display_user has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\user::display', DEBUG_DEVELOPER);
        if (!empty($user)) {
            return parent::rb_display_user($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_signin');
    }
}