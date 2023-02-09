<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totara.com>
 * @package tool_smtp_test
 */

namespace tool_smtp_test;

use core_user;

defined('MOODLE_INTERNAL') || die;

class smtp_test {

    static public function send_test_email($to, $subject, $message, $user = false) {
        global $DB;

        $messagehtml = text_to_html($message);
        $supportuser = \core_user::get_support_user();

        if (!$user) {
            $user = \totara_core\totara_user::get_external_user($to);
            debugging("Not a Totara user.");
        }
        else if ($user->email != $to) {
            debugging("Email address and user record do not match.");
            return false;
        }

        $user->mailformat = 1;  // Always send HTML version as well.

        // Handle some situations that email_to_user() silently ignores
        if ((isset($user->auth) && $user->auth == 'nologin') or (isset($user->suspended) && $user->suspended)) {
            debugging("user->auth is set to 'nologin', or user is suspended.");
            return false;
        }
        if (!empty($user->tenantid)) {
            if ($DB->record_exists('tenant', ['id' => $user->tenantid, 'suspended' => 1])) {
                debugging("user is tenant suspended.");
                return false;
            }
        }

        return email_to_user($user, $supportuser, $subject, $message, $messagehtml);
    }
}