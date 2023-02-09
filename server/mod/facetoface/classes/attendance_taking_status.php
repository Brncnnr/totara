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
 * attendance_taking_status enumeration.
 */
abstract class attendance_taking_status {
    const UNKNOWN = 0;
    const CLOSED_UNTILEND = -1;
    const CLOSED_UNTILSTART = -2;
    const CLOSED_UNTILSTARTFIRST = self::CLOSED_UNTILSTART;
    const CLOSED_UNTILSTARTLAST = -3; // valid only for seminar event
    const NOTAVAILABLE = -10;
    const CANCELLED = -11;
    const OPEN = 1;
    const ALLSAVED = 2;

    /**
     * Return true if attendance tracking is available.
     *
     * @param integer $value one of attendance_taking_status constants
     * @return boolean
     */
    public static function is_available(int $value): bool {
        // Any natural number is success. Any negative number is failre.
        return $value > 0;
    }
}
