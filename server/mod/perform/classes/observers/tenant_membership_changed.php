<?php
/**
 * This file is part of Totara Learn
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_userstatus
 */

namespace mod_perform\observers;

use core\event\user_tenant_membership_changed;
use core\orm\collection;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\activity;

class tenant_membership_changed {

    public static function updated(user_tenant_membership_changed $event) {
        // Check all active assignments for the user
        // and if the activity is in a different context than the users
        // mark it as deleted
        $track_user_assignments = track_user_assignment::repository()
            ->where('subject_user_id', $event->relateduserid)
            ->where('deleted', 0)
            ->with('track.activity')
            ->get();

        $contexts = [];

        /** @var collection|track_user_assignment[] $track_user_assignments */
        foreach ($track_user_assignments as $assignment) {
            $activity = activity::load_by_entity($assignment->track->activity);
            if (!isset($contexts[$activity->id])) {
                $contexts[$activity->id] = $activity->get_context();
            }

            if ($contexts[$activity->id]->tenantid != $event->get_new_tenant_id()) {
                $assignment->deleted = true;
                $assignment->save();
            }
        }
    }

}