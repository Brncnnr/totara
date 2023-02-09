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
 * event_time enumeration.
 */
abstract class event_time {
    /** All events */
    const ALL = 0;

    /** Future events and wait-listed events */
    const UPCOMING = 1;

    /** Ongoing events */
    const INPROGRESS = 2;

    /** Past events and cancelled events */
    const OVER = 3;

    /** Future events only */
    const FUTURE = 4;

    /** Past events only */
    const PAST = 5;

    /** Wait-listed events only */
    const WAITLISTED = 6;

    /** Cancelled events only */
    const CANCELLED = 7;
}
