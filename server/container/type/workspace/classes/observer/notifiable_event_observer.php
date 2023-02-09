<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

namespace container_workspace\observer;

use container_workspace\totara_notification\resolver\user_added;
use container_workspace\totara_notification\workspace_muter;
use core\event\user_enrolment_created;
use core\orm\query\builder;
use totara_notification\external_helper;

/**
 * Observe notifiable events related to workspaces
 */
class notifiable_event_observer {

    /**
     * Called whenever a user is enrolled into a workspace.
     *
     * @param user_enrolment_created $event
     * @return void
     */
    public static function user_added_to_workspace(user_enrolment_created $event) {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        // Only react if we're working with a workspace (courses can end up here too)
        if ($event->other['containertype'] !== 'container_workspace') {
            return;
        }

        $data = [
            'workspace_id' => $event->get_data()['courseid'],
            'user_id' => $event->get_data()['relateduserid']
        ];

        // Is this notification muted?
        if (workspace_muter::is_muted(user_added::class, $data['workspace_id'], $data['user_id'])) {
            return;
        }

        // Check how many times this user is enrolled into this workspace
        // It's possible for the user to be enroled in multiple cohorts, so we group by the first cohort ID
        $enrolment_methods = builder::table('user_enrolments', 'ue')
            ->select_raw('MIN(ue.id) AS user_enrolment_id')
            ->add_select('e.enrol')
            ->join(['enrol', 'e'], 'ue.enrolid', 'e.id')
            ->where('ue.userid', $data['user_id'])
            ->where('e.courseid', $data['workspace_id'])
            ->where('ue.status', ENROL_USER_ACTIVE)
            ->group_by(['e.enrol'])
            ->get(true);

        // If there is more than one method, this user is already a member, do not notify them.
        // If there are no methods, this user is no longer a member, do not notify them.
        // If there is exactly one method, confirm it's either a `manual` or `cohort`.
        // That indicates this is a user member added via Audience or via the Owner, so notify them.
        if ($enrolment_methods->count() !== 1) {
            return;
        }
        $enrolment_method = $enrolment_methods->first();
        if (!in_array($enrolment_method->enrol, ['cohort', 'manual'])) {
            return;
        }

        // It is entirely possible that we have multiple enrolment events fired within the same time period.
        // We only want to send one notification, so if that's the case, check if this specific event came first.
        if ($event->objectid != $enrolment_method->user_enrolment_id) {
            return;
        }

        // We have exactly one enrolment, and it's either audience or manual, so trigger the notification.
        external_helper::create_notifiable_event_queue(new user_added($data));
    }
}
