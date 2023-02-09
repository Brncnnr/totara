<?php
/*
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
 * @package core_enrol
 */

namespace core\webapi\resolver\mutation;

use \core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;

/**
 * Mutation to move a job assignment to a new position.
 */
class enrol_attempt_self_enrolment extends mutation_resolver {

    /**
     * Self-completes a course as the current user.
     *
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec) {
        global $USER, $DB;

        $vars = $args['input'];

        // Courseid must be provided
        if (empty($vars['instanceid']) || empty($vars['courseid'])) {
            throw new \coding_exception('missing arguments for attempt_self_enrolment mutation');
        }

        $data = new \stdClass();
        $courseid = $vars['courseid'];
        $instanceid = $vars['instanceid'];

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

        // Throw a fit if the instance wasn't found, or didn't end up being a self enrolment instance.
        if (empty($instance) || $instance->enrol != 'self') {
            throw new \coding_exception('invalid arguments for attempt_self_enrolment mutation');
        }

        // If a password is required, check we have it and that it matches.
        if (!empty($instance->password)) {
            if (empty($vars['password'])) {
                throw new \coding_exception('invalid arguments for attempt_self_enrolment mutation');
            }

            $password = $vars['password'];
            if ($instance->password != $password) {
                return [
                    'success' => false,
                    'msg_key' => get_string('passwordinvalid', 'enrol_self')
                ];
            } else {
                // Set the password into expected format.
                $data->enrolpassword = $password;
            }
        }

        $enrol = enrol_get_plugin('self');
        if (empty($enrol)) {
            // Just in case someone has removed the self-enrolment plugin.
            throw new \coding_exception('missing self enrolment plugin');
        }

        // Attempt to enrol the user in the course.
        $status = $enrol->can_self_enrol($instance, true);
        if ($status === true) {
            $enrol->enrol_self($instance, $data);

            // Seems that function doesn't return what it says it should so double check success.
            $params = ['userid' => $USER->id, 'enrolid' => $instance->id, 'status' => ENROL_USER_ACTIVE];
            $enrolled = $DB->record_exists('user_enrolments', $params);
        } else {
            $enrolled = false;
            if (is_string($status)) {
                $msgkey = $status;
            }
        }

        return [
            'success' => $enrolled,
            'msg_key' => $msgkey ?? null
        ];
    }

    public static function get_middleware(): array {
        return [
            new require_login()
        ];
    }
}

