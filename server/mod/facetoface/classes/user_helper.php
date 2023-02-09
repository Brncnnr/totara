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

use stdClass;
use core_user;
use html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Additional user functionality.
 */
final class user_helper {
    /**
     * Get the user profile link for the user.
     *
     * @param integer|stdClass $userorid User id or object
     * @param string|null $fullname Set null to get the full name via fullname()
     * @param array|null $params Additional parameter set for the profile link
     * @return string
     */
    public static function get_profile($userorid, string $fullname = null, array $params = null): string {
        if (is_int($userorid)) {
            $userid = $userorid;
            $user = null;
        } else {
            $userid = $userorid->id;
            $user = $userorid;
        }
        if (!empty($userid) && !empty($user = $user ?? core_user::get_user($userid))) {
            if ($fullname === null) {
                $fullname = fullname($user);
            }
            $url = user_get_profile_url($user);
            if ($url) {
                if ($params) {
                    $url->params($params);
                }
                return html_writer::link($url, $fullname);
            } else {
                return $fullname;
            }
        } else {
            return clean_string(get_string('unknownuser'));
        }
    }

    /**
     * Get the datetime with the user profile link.
     *
     * @param integer $time Unix epoch timestamp
     * @param integer|stdClass $userorid User id or object
     * @param string|null $fullname Set null to get the full name via fullname()
     * @param array|null $params Additional parameter set for the profile link
     * @return string
     */
    public static function get_timestamp_and_profile(int $time, $userorid, string $fullname = null, array $params = null): string {
        $obj = new stdClass();
        $obj->time = empty($time) ? '' : userdate($time);
        $obj->user = self::get_profile($userorid, $fullname, $params);
        return get_string('timestampbyuser', 'mod_facetoface', $obj);
    }
}
