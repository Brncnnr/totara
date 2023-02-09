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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task\service\data;

use coding_exception;
use core\orm\query\builder;
use mod_perform\entity\activity\activity;

/**
 * A collection of activity configuration used in subject/participant instance creation.
 * This improves performance by memorizing the activity information and checks called
 * during the creation processes of subjects and participants.
 *
 * @package mod_perform\task\service\data
 */
class subject_instance_activity_collection {

    /**
     * List of subject instance activity configurations.
     *
     * @var subject_instance_activity[]
    */
    private $activities = [];

    /**
     * Add activity configuration to collection.
     *
     * @param activity $activity
     * @return self
     */
    public function add_activity_config(activity $activity): self {
        if (empty($this->activities[$activity->id])) {
            $this->activities[$activity->id] = new subject_instance_activity($activity);
        }

        return $this;
    }

    /**
     * Get subject instance activity configuration.
     *
     * @param int $activity_id
     * @return subject_instance_activity
     */
    public function get_activity_config(int $activity_id): subject_instance_activity {
        if (empty($this->activities[$activity_id])) {
            throw new coding_exception('Activity not loaded into collection');
        }
        return $this->activities[$activity_id];
    }

    /**
     * Load activity configurations into collection if they are not loaded already.
     *
     * @param array $activity_ids
     * @return self
     */
    public function load_activity_configs_if_missing(array $activity_ids): self {
        $loaded_activities = array_keys($this->activities);
        $missing_activities = array_diff(array_unique($activity_ids), $loaded_activities);

        if (!empty($missing_activities)) {
            $this->load_activities_from_database($missing_activities);
        }

        return $this;
    }

    /**
     * Loads activities from database.
     *
     * @param array $activity_ids
     * @return void
     */
    private function load_activities_from_database(array $activity_ids): void {
        $activity_id_chunks = array_chunk($activity_ids, builder::get_db()->get_max_in_params());

        foreach ($activity_id_chunks as $activity_id_chunk) {
            $activities = activity::repository()
                ->where('id', $activity_id_chunk)
                ->eager_load_instance_creation_data()
                ->get();

            foreach ($activities as $activity) {
                $this->add_activity_config($activity);
            }
        }
    }
}