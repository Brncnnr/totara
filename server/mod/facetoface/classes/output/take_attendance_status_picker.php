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
use core\output\template;
use stdClass;


/**
 * Creating the widget for status picker, within taking attendance form.
 *
 * Class take_attendance_status_picker
 * @package mod_facetoface\output
 */
class take_attendance_status_picker extends template {
    /**
     * @param stdClass  $attendee       The current attendee/user record
     * @param array     $statusoptions  List of options for this attendee to pick
     * @param bool      $disabled       Whether this list is disabled or not
     *
     * @return take_attendance_status_picker
     */
    public static function create(stdClass $attendee,
                                  array $statusoptions,
                                  bool $disabled = false): take_attendance_status_picker {
        $data = [
            'name' => "submissionid_{$attendee->submissionid}",
            'label' => get_string('takeattendance_label', 'mod_facetoface', clean_string(fullname($attendee))),
            'options' => [],
            'disabled' => $disabled
        ];

        foreach ($statusoptions as $code => $label) {
            $data['options'][] = [
                'option_label' => $label,
                'selected' => $attendee->statuscode == $code,
                'value' => $code
            ];
        }

        return new static($data);
    }
}
