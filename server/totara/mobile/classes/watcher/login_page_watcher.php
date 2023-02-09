<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\watcher;

use core\hook\login_page_start;
use core\hook\login_page_login_complete;
use totara_mobile\local\util as mobile_util;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook watcher to capture login page setup and form submission.
 */
final class login_page_watcher {
    /**
     * A watcher to set up the login page for mobile app use.
     *
     * @param login_page_start $hook
     * @return void
     */
    public static function webview_login_setup(login_page_start $hook): void {
        if (!get_config('totara_mobile', 'enable')) {
            // Do nothing if the mobile app is disabled.
            return;
        }

        // Totara: mobile device registration hook.
        mobile_util::login_page_hook_start();
    }

    /**
     * A watcher to clean up session redirect to device request page after webview login.
     *
     * @param login_page_login_complete $hook
     * @return void
     */
    public static function webview_login_complete(login_page_login_complete $hook): void {
        if (!get_config('totara_mobile', 'enable')) {
            // Do nothing if the mobile app is disabled.
            return;
        }

        // Totara: mobile device registration hook.
        mobile_util::login_page_hook_loggedin();
    }
}