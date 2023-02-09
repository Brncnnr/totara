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
 * Provide the type-safe bookedsession field of session_data.
 *
 * {facetoface_signups} fields
 * @property-read integer $id
 * @property-read integer $sessionid
 * @property-read integer $userid
 * @property-read string|null $discountcode
 * @property-read integer $notificationtype
 * @property-read integer $archived
 * @property-read integer $bookedby
 * @property-read integer $managerid
 * @property-read integer $jobassignmentid
 *
 * @property-read integer $facetoface
 * @property-read integer $cancelledstatus
 * @property-read integer $timemodified
 * @property-read integer $timecreated
 * @property-read integer $timegraded
 * @property-read integer $statuscode
 * @property-read integer $timecancelled
 * @property-read integer $mailedconfirmation
 */
final class session_signup_data extends \stdClass {
}
