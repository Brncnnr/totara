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

namespace mod_facetoface\output;
defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, seminar_session_list, seminar_session};
use core\output\template;
use stdClass;
use moodle_url;

/**
 * Creating a widget of choosing between sessions within taking attendance form.
 *
 * Class take_attendance_session_picker
 * @package mod_facetoface\output
 */
class take_attendance_session_picker extends template {
    /**
     * @param seminar_event         $seminarevent
     *
     * @param seminar_session_list  $sessions       The list of session dates for a seminar event,
     *                                              it is needed for rendering the selection box.
     *
     * @param moodle_url            $url            Base url, without any parameters
     *
     * @param int                   $sessiondateid  Current selected session date id.
     *
     * @param string                $name
     *
     * @return take_attendance_session_picker
     */
    public static function create(
        seminar_event $seminarevent,
        seminar_session_list $sessions,
        moodle_url $url,
        int $sessiondateid = 0,
        string $name = 'event-sessions'
    ): take_attendance_session_picker {
        $data = [
            'name' => $name,
            'sessions' => [],
            'sessionid' => $seminarevent->get_id(),
            'url' => $url->out(),
            'summary' => $sessions->get_summary()
        ];

        $identifier = 'eventupcoming';
        if ($seminarevent->is_over()) {
            $identifier = 'eventover';
        } else if ($seminarevent->is_progress()) {
            $identifier = 'eventinprogress';
        }

        $data['sessions'][] = [
            'value' => '0',
            'disabled' => false,
            'selected' => $sessiondateid == 0,
            'option_label' => get_string($identifier, 'mod_facetoface'),
        ];

        /**
         * @var int $i
         * @var seminar_session $session
         */
        foreach ($sessions as $i => $session) {
            $id = $session->get_id();

            $sessionurl = clone $url;
            $sessionurl->param("sd", $id);

            $sessionidentifier = 'inprogress';
            if ($session->is_over()) {
                $sessionidentifier = 'over';
            } else if ($session->is_upcoming()) {
                $sessionidentifier = 'upcoming';
            }

            $o = new stdClass();
            $o->datetime = $session->get_time_description('strftimedatetime');
            $o->status = get_string($sessionidentifier, 'mod_facetoface');

            $data['sessions'][] = [
                'value' => $id,
                'disabled' => !$session->is_attendance_open(),
                'selected' => $sessiondateid == $id,
                'option_label' => get_string('sessioninformation', 'mod_facetoface', $o),
            ];
        }

        return new static($data);
    }
}
