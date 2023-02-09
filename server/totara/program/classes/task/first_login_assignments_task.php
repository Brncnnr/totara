<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_program
 */

namespace totara_program\task;

use totara_core\advanced_feature;

class first_login_assignments_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('firstloginassignmentstask', 'totara_program');
    }

    /**
     * Looks for users with future assignment records who have logged in
     *
     * If any are found an event is triggered to activate the future assignment.
     * This function should only be needed to catch logins via third-party
     * authentication plugins, since all the existing auth plugins have had an
     * event trigger added.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/program/lib.php');

        // Don't run programs cron if programs and certifications are disabled.
        if (advanced_feature::is_disabled('programs') &&
            advanced_feature::is_disabled('certifications')) {
            return;
        }

        $pending_user_sql = "SELECT pfa.id, u.id as userid, pfa.programid, p.certifid
                            FROM {user} u
                            INNER JOIN {prog_future_user_assignment} pfa
                            ON pfa.userid = u.id
                            INNER JOIN {prog} p
                            ON pfa.programid = p.id
                            WHERE u.firstaccess > 0";

        $pending_users = $DB->get_records_sql($pending_user_sql);
        foreach ($pending_users as $pending_user) {
            if (empty($pending_user->certifid)) {
                // Skip update if the program is not accessible to the user.
                if (totara_program_is_viewable($pending_user->programid, $pending_user->userid)) {
                    prog_assignments_firstlogin($pending_user->userid);
                }
            } else if (totara_certification_is_viewable($pending_user->programid, $pending_user->userid)) {
                prog_assignments_firstlogin($pending_user->userid);
            }
        }
    }
}

