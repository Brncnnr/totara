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

namespace mod_facetoface\output;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\attendance\event_attendee;
use mod_facetoface\signup_status;
use mod_facetoface\seminar;
use mod_facetoface\grade_helper;

/**
 * An input box for event grade
 */
class event_grade_input extends \core\output\template {
    /**
     * Instantiate event_grade_input.
     *
     * @param event_attendee        $attendee
     * @param signup_status|null    $status
     * @param bool                  $disabled  Set false to disable the input field
     * @param string|null           $step The step is a step attribute specifies the interval between legal numbers in an <input> element.
     *
     * @return event_grade_input
     */
    public static function create(event_attendee $attendee,
                                  signup_status $status = null,
                                  bool $disabled = false,
                                  $step = null): event_grade_input {

        $separator = get_string('decsep', 'langconfig');
        $value = $status !== null ? $status->get_grade() : null;

        $data = [
            'name' => "submissiongradeid_{$attendee->get_signupid()}",
            'placeholder' => get_string('gradeinput_placeholder', 'facetoface'),
            'disabled' => $disabled,
            'label' => get_string('gradeinput_label', 'facetoface', clean_string(fullname($attendee))),
            'min' => seminar::GRADE_PASS_MINIMUM,
            'max' => seminar::GRADE_PASS_MAXIMUM,
            'value' => grade_helper::format($value, $attendee->course),
            'type' => $separator === '.' ? 'number' : 'text',
            'step' => $step,
        ];

        return new static($data);
    }
}
