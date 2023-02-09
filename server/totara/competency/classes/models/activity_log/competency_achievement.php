<?php
/**
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models\activity_log;

use coding_exception;
use core\orm\entity\entity as core_entity;
use totara_competency\entity\competency_achievement as competency_achievement_entity;
use totara_competency\models\activity_log;
use totara_hierarchy\entity\scale_value;

class competency_achievement extends activity_log {

    /**
     * @var scale_value
     */
    protected $scale_value;

    /**
     * Load an instance of this model using data from the entity passed in.
     *
     * @param core_entity $entity
     * @return activity_log
     */
    public static function load_by_entity(core_entity $entity): activity_log {
        if (!($entity instanceof competency_achievement_entity)) {
            throw new coding_exception('Invalid entity', 'Entity must be instance of competency_achievement');
        }

        $model = new competency_achievement();
        $model->set_entity($entity);
        $model->scale_value = $entity->value;
        return $model;
    }

    /**
     * Timestamp of the date corresponding to this data.
     *
     * @return int
     */
    public function get_date(): int {
        return $this->get_entity()->time_created;
    }

    /**
     * Gets the human-readable description for an competency achievement type instance.
     *
     * @return string
     */
    public function get_description(): string {
        if (!$this->has_scale_value()) {
            return get_string('activity_log_no_rating', 'totara_competency');
        }
        return get_string('activity_log_rating', 'totara_competency', [
            'scale_value_name' => $this->scale_value->name,
        ]);
    }

    /**
     * Get the activity log data for how this achievement was achieved.
     *
     * @return activity_log
     */
    public function get_achieved_via(): activity_log {
        $achievement = $this->get_entity();
        return competency_achieved_via::load_by_entity($achievement);
    }

    /**
     * @return bool|null True if this achievement made the user proficient in this competency.
     */
    public function get_proficient_status(): ?bool {
        return $this->get_entity()->proficient;
    }

    /**
     * Does this achievement have a rating with a value?
     *
     * @return bool
     */
    public function has_scale_value(): bool {
        return !empty($this->scale_value);
    }
}