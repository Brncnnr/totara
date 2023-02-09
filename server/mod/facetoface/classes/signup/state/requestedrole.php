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

namespace mod_facetoface\signup\state;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\signup\condition\{
    approval_admin_not_required,
    approval_manager_required,
    approval_not_required,
    approval_role_required,
    booking_common,
    event_is_cancelled,
    event_is_not_cancelled,
    signup_awaits_approval,
    waitlist_common
};
use mod_facetoface\signup;
use mod_facetoface\signup\restriction\{
    actor_has_role,
    actor_is_admin,
    actor_is_manager_or_admin
};
use mod_facetoface\signup\transition;
use mod_facetoface\event\booking_requested;
use mod_facetoface\event\abstract_signup_event;

/**
 * This class represents requested with role approval state.
 */
class requestedrole extends state implements interface_event {
    final public function get_map() : array {
        return [
            // Approval no longer required.
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_not_required::class
            ),
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                approval_not_required::class
            ),

            // A user with the specified role approves or declines the request.
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_role_required::class,
                approval_admin_not_required::class
            )->with_restrictions(
                actor_has_role::class
            ),
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                approval_role_required::class,
                approval_admin_not_required::class
            )->with_restrictions(
                actor_has_role::class
            ),
            transition::to(new declined($this->signup))->with_conditions(
                approval_role_required::class,
                event_is_not_cancelled::class
            )->with_restrictions(
                actor_has_role::class
            ),

            // The role approval is not longer required but manager approval is now required
            transition::to(new requested($this->signup))->with_conditions(
                booking_common::class,
                approval_manager_required::class,
                approval_admin_not_required::class
            )->with_restrictions(
                actor_is_manager_or_admin::class
            ),

            // Admin  approves or declines the request.
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
            )->with_restrictions(
                actor_is_admin::class
            ),
            transition::to(new declined($this->signup))->with_conditions(
                event_is_not_cancelled::class
            )->with_restrictions(
                actor_is_admin::class
            ),

            // The seminar event is cancelled.
            transition::to(new event_cancelled($this->signup))->with_conditions(
                event_is_cancelled::class
            ),
            // The user has cancelled.
            transition::to(new user_cancelled($this->signup)),

            // Rescue 'request approval' sign-up status to be able to move to declined.
            transition::to(new declined($this->signup))->with_conditions(
                approval_not_required::class,
                signup_awaits_approval::class
            ),
        ];
    }

    public static function get_code() : int {
        return 44;
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    public function get_message(): string {
        global $DB;

        $sql = 'SELECT fsr.*, u.*
                  FROM {facetoface_session_roles} fsr
                  JOIN {facetoface_sessions} fs
                    ON fsr.sessionid = fs.id
                  JOIN {facetoface} f
                    ON fs.facetoface = f.id
                  JOIN {user} u
                    ON u.id = fsr.userid
                 WHERE f.approvalrole IS NOT NULL
                   AND fsr.roleid = f.approvalrole
                   AND fs.id = :sid';
        $recs = $DB->get_records_sql($sql, ['sid' => $this->get_signup()->get_sessionid()]);
        $names = implode(',', array_map(function ($e) { return fullname($e); }, $recs));
        return get_string('bookingcompleted_roleapprovalrequired2', 'facetoface', $names);
    }

    /**
     * Get action label for getting into state.
     * @return string
     */
    public function get_action_label(): string {
        return get_string('signupandrequest', 'mod_facetoface');
    }

    /**
     * Get event to fire when entering state
     *
     * @return abstract_signup_event
     */
    public function get_event() : abstract_signup_event {
        $cm = $this->signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        return booking_requested::create_from_signup($this->signup, $context);
    }

    /**
     * Get the requested status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_requestedrole', 'mod_facetoface');
    }
}
