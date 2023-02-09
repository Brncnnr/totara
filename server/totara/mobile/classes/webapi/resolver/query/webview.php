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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\query;

use core\webapi\execution_context;

class webview extends \core\webapi\query_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $USER, $DB;

        if (!has_capability('totara/mobile:use', \context_user::instance($USER->id))) {
            return false;
        }

        if (!($ec instanceof \totara_mobile\webapi\execution_context)) {
            throw new \coding_exception('invalid webview request');
        }

        $deviceid = $ec->get_device_id();
        if (!$deviceid) {
            throw new \coding_exception('invalid webview request');
        }

        $secret = $args['secret'];

        $webview = $DB->get_record('totara_mobile_webviews', ['secret' => $secret, 'deviceid' => $deviceid]);
        if (!$webview or !$webview->sessionid) {
            return false;
        }

        $session = $DB->get_record('sessions', ['id' => $webview->sessionid]);
        if (!$session or $session->sid !== $webview->sid or $session->userid != $USER->id) {
            return false;
        }

        if (!\core\session\manager::session_exists($session->sid)) {
            return false;
        }

        return true;
    }
}
