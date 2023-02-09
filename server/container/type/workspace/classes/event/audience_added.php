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
 * @package container_workspace
 */

namespace container_workspace\event;

use container_workspace\workspace;
use core\entity\cohort;
use core\event\base;

/**
 * Event to trigger when audience gets added for bulk adding it's members to the workspace
 * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
 */
class audience_added extends base {

    /**
     * @param workspace $workspace
     * @param array $cohort_ids
     * @param int $number_of_members_added
     * @param int|null $actor_id
     *
     * @return self|base
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public static function from_workspace(
        workspace $workspace,
        array $cohort_ids,
        int $number_of_members_added,
        ?int $actor_id = null
    ): self {
        global $USER;
        debugging("audience_added::from_workspace has been deprecated.", DEBUG_DEVELOPER);

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $owner_id = $workspace->get_user_id();
        $workspace_id = $workspace->get_id();

        /** @var workspace_viewed $event */
        $event = static::create([
            'courseid' => $workspace_id,
            'objectid' => $workspace_id,
            'userid' => $actor_id,
            'relateduserid' => $owner_id,
            'context' => $workspace->get_context(),
            'other' => [
                'cohort_ids' => $cohort_ids,
                'number_of_members_added' => $number_of_members_added
            ]
        ]);

        return $event;
    }

    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = 'course';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * @return string
     */
    public static function get_name() {
        return get_string('event_bulk_audience_added', 'container_workspace');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $cohorts = cohort::repository()
            ->where('id', $this->other['cohort_ids'])
            ->get();

        $cohort_names = [];
        foreach ($cohorts as $cohort) {
            $cohort_names[] = $cohort->id.' ('.format_string($cohort->name).')';
        }

        $cohort_names = implode(', ', $cohort_names);
        return sprintf(
            '%s members from audience(s) %s added in bulk to the workspace with id %d',
            $this->other['number_of_members_added'] ?? 'unknown number of',
            $cohort_names,
            $this->objectid
        );
    }
}