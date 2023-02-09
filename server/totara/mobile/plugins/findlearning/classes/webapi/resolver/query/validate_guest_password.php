<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

namespace mobile_findlearning\webapi\resolver\query;

use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;

/**
 * Class current_learning extends totara_core_my_current_learning query
 *
 * @package totara_mobile\webapi\resolver\query
 */
class validate_guest_password extends query_resolver {

    /**
     * Check whether a valid guest passord has been handed through
     *
     * @param array $args
     * @param execution_context $ec
     * @return stdClass[]
     */
    public static function resolve(array $args, execution_context $ec) {
        global $USER, $DB;

        $vars = $args['input'];

        // Courseid must be provided
        if (empty($vars['instanceid']) || empty($vars['courseid'])) {
            throw new \coding_exception('missing arguments for validate_guest_password query');
        }

        $courseid = $vars['courseid'];
        $instanceid = $vars['instanceid'];

        $plugin = enrol_get_plugin('guest');
        if (empty($plugin)) {
            // Just in case someone has removed the guest access plugin.
            throw new \coding_exception('missing guest access plugin');
        }

        // Quickly loop through the courses instances to find the one we're looking for.
        $instance = null;
        if ($instances = enrol_get_instances($courseid, true)) {
            foreach ($instances as $enrol) {
                if ($enrol->id == $instanceid) {
                    $instance = $enrol;
                    break;
                }
            }
        }

        // Throw a fit if the instance wasn't found, or didn't end up being a guest instance.
        if (empty($instance) || $instance->enrol != 'guest') {
            throw new \coding_exception('invalid arguments for validate_guest_password query');
        }

        // If a password is required, check we have it and that it matches.
        $password = null;
        if (!empty($instance->password)) {
            if (empty($vars['password'])) {
                throw new \coding_exception('missing arguments for validate_guest_password query');
            }

            $password = $vars['password'];
            if ($instance->password != $password) {
                return [
                    'success' => false,
                    'message' => get_string('passwordinvalid', 'enrol_guest')
                ];
            } else {
                // We can't set up guest access, but set this for the checks below.
                $USER->enrol_guest_passwords[$instance->id] = $password;
            }
        }

        // Attempt to enrol the user in the course.
        $status = $plugin->try_guestaccess($instance);
        if ($status === true || (is_int($status) && $status > time())) {
            $enrolled = true;
        } else {
            $enrolled = false;
            if (is_string($status)) {
                $msgkey = $status;
            }
        }

        return [
            'success' => $enrolled,
            'message' => $msgkey ?? null
        ];
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }
}
