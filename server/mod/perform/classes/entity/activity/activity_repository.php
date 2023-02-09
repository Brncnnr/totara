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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\collection;
use core\orm\entity\repository;
use mod_perform\state\activity\draft;
use totara_core\entity\relationship;

class activity_repository extends repository {

    /**
     * Filter by visible activities only, this will include course visibility checks
     *
     * @param int|null $for_user_id if omitted will check for logged-in user
     * @return $this
     */
    public function filter_by_visible(int $for_user_id = null): self {
        global $CFG;
        require_once($CFG->dirroot . "/totara/coursecatalog/lib.php");

        [$totara_visibility_sql, $totara_visibility_params] = totara_visibility_where($for_user_id);

        $this->join('course', 'course', 'id')
            ->where_raw($totara_visibility_sql, $totara_visibility_params);

        return $this;
    }

    /**
     * Return all non-draft activities, independent of visibility settings.
     *
     * @return $this
     */
    public function filter_by_not_draft(): self {
        $this->where('status', '<>', draft::get_code());

        return $this;
    }

    /**
     * @param int ...$subject_user_ids
     * @return collection|activity[]
     */
    public function find_by_subject_user_id(int ...$subject_user_ids): collection {
        return $this->as('activity')
            ->select_raw('distinct activity.*')
            ->join([track::TABLE, 'track'], 'activity.id', 'track.activity_id')
            ->join([track_user_assignment::TABLE, 'track_user_assignment'], 'track.id', 'track_user_assignment.track_id')
            ->where('track_user_assignment.subject_user_id', $subject_user_ids)
            ->where_not_in('activity.status', [draft::get_code()])
            ->filter_by_visible()
            ->get();
    }

    /**
     * Get all the relationships involved in an activity.
     *
     * @param int $activity_id
     * @return collection|relationship[]
     */
    public function get_responding_relationships(int $activity_id): collection {
        return relationship::repository()->as('r')
            ->select_raw('distinct r.*')
            ->join([section_relationship::TABLE, 'sr'], 'sr.core_relationship_id', 'r.id')
            ->join([section::TABLE, 's'], 's.id', 'sr.section_id')
            ->where('s.activity_id', $activity_id)
            ->where('sr.can_answer', true)
            ->order_by('r.sort_order')
            ->get();
    }

    /**
     * Get activity configuration needed for participant instance creation.
     *
     * @return activity_repository
     */
    public function eager_load_instance_creation_data() {
        return $this
            ->with('notifications.recipients')
            ->with('sections.section_relationships.core_relationship');
    }
}