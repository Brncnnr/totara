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

namespace mod_facetoface\internal;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide the type-safe sessiondates field of session_data.
 *
 * {facetoface_sessions_dates} fields
 * @property-read integer $id
 * @property-read integer $sessionid
 * @property-read string  $sessiontimezone
 * @property-read integer $roomid
 * @property-read integer $timestart
 * @property-read integer $timefinish
 */
final class session_date_data extends \stdClass {
}
