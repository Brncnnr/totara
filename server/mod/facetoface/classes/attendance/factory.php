<?php
/*
 * This file is part of Totara Learn
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
 * @package mod_facetoface
 */

namespace mod_facetoface\attendance;
defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, seminar_session};
use moodle_url;
use moodle_exception;
use context;

/**
 * Class factory
 * @package mod_facetoface\attendance
 */
final class factory {
    /**
     * Get attendance_tracking content, whether it is a downloadable content or browser content.
     *
     * @param seminar_event $seminarevent
     * @param moodle_url    $url
     *
     * @param string|bool   $download
     *
     * @param context       $context        Context is needed to calculate the ability of taking attendance.
     *
     * @param int           $sessiondateid  This could be a zero, or any number so that the attendance tracking knows
     *                                      which level it is going to generate content for. Zero means it is an event
     *                                      level, otherwise larger than zero and a valid session date one, then it is
     *                                      a session level content.
     *
     * @return attendance_tracking
     */
    public static function get_attendance_tracking(seminar_event $seminarevent, moodle_url $url,
                                                   $download, context $context,
                                                   int $sessiondateid = 0): attendance_tracking {
        $sessions = $seminarevent->get_sessions();

        if ($sessiondateid > 0) {
            // Just to prevent those session dates that is not a valid one. If it is a negative
            // number, then by default, we display it as event level.
            /** @var seminar_session|null $session */
            $session = $sessions->get($sessiondateid);
            if (null == $session) {
                throw new moodle_exception("nosessionfound", "facetoface");
            }
        }

        if (!$download) {
            $attendancetracking = new take_attendance_tracking($seminarevent, $url, $context, $sessions);
        } else {
            $attendancetracking = new download_attendance_tracking($seminarevent, $download);
        }

        $attendancetracking->set_sessiondate_id($sessiondateid);
        return $attendancetracking;
    }
}