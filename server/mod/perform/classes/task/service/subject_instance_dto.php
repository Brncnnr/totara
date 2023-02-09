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
 * @package mod_perform
 */

namespace mod_perform\task\service;

use coding_exception;
use mod_perform\entity\activity\subject_instance;

/**
 * A simple readonly object to transfer data from one service to another.
 *
 * This avoids a full entity to be passed down via hooks to watchers we might not
 * have control over.
 *
 * @property-read int $id
 * @property-read int $track_user_assignment_id
 * @property-read int $subject_user_id
 * @property-read int|null $job_assignment_id
 * @property-read int $created_at
 * @property-read int|null $updated_at
 * @property-read int $track_id
 * @property-read int $activity_id
 * @property-read int $status
 */
class subject_instance_dto {

    /** @var int */
    protected $id;
    /** @var int */
    protected $track_user_assignment_id;
    /** @var int */
    protected $subject_user_id;
    /** @var int|null */
    protected $job_assignment_id;
    /** @var int */
    protected $created_at;
    /** @var int|null */
    protected $updated_at;
    /** @var int */
    protected $track_id;
    /** @var int */
    protected $activity_id;
    /** @var int */
    protected $status;

    /**
     * Create a new dto from a given entity
     *
     * @param subject_instance $subject_instance
     * @param array|null $track_data Optional track data containing track id and activity id.
     *
     * @return static
     */
    public static function create_from_entity(subject_instance $subject_instance, array $track_data = null): self {
        $instance = new self();
        $instance->id = (int) $subject_instance->id;
        $instance->track_user_assignment_id = (int) $subject_instance->track_user_assignment_id;
        $instance->subject_user_id = (int) $subject_instance->subject_user_id;
        $instance->job_assignment_id = (int) $subject_instance->job_assignment_id;
        $instance->created_at = (int) $subject_instance->created_at;
        $instance->updated_at = (int) $subject_instance->updated_at;
        $instance->activity_id = empty($track_data['activity_id'])
            ? (int) $subject_instance->track->activity_id
            : $track_data['activity_id'];
        $instance->track_id = empty($track_data['track_id'])
            ? (int) $subject_instance->track->id
            : $track_data['track_id'];
        $instance->status = (int) $subject_instance->status;

        return $instance;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function get_track_user_assignment_id(): int {
        return $this->track_user_assignment_id;
    }

    /**
     * @return int
     */
    public function get_subject_user_id(): int {
        return $this->subject_user_id;
    }

    /**
     * @return int|null
     */
    public function get_job_assignment_id(): ?int {
        return $this->job_assignment_id;
    }

    /**
     * @return int
     */
    public function get_created_at(): int {
        return $this->created_at;
    }

    /**
     * @return int|null
     */
    public function get_updated_at(): ?int {
        return $this->updated_at;
    }

    /**
     * @return int
     */
    public function get_track_id(): int {
        return $this->track_id;
    }

    /**
     * @return int
     */
    public function get_activity_id(): int {
        return $this->activity_id;
    }

    /**
     * Returns the status
     *
     * @return int
     */
    public function get_status(): int {
        return $this->status;
    }

    final public function __get($name) {
        $getter_name = "get_{$name}";
        if (method_exists($this, $getter_name)) {
            return $this->{$getter_name}();
        }

        throw new coding_exception('Unknown getter method for '.$name);
    }

    final public function __set($name, $value) {
        throw new coding_exception('This dto is ready-only and cannot be modified');
    }

    final public function __isset($name) {
        return method_exists($this, "get_{$name}");
    }

}