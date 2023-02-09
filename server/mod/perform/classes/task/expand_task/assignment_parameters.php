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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task\expand_task;

use mod_perform\entity\activity\track_user_assignment;
use stdClass;

/**
 * Represent expand task assignment parameters.
 */
class assignment_parameters {

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var int|null
     */
    protected $job_assignment_id;

    /**
     * assignment_parameters constructor.
     *
     * Factory functions should be used instead.
     *
     * @param int $user_id
     * @param int|null $job_assignment_id
     */
    protected function __construct(int $user_id, ?int $job_assignment_id) {
        $this->user_id = $user_id;
        $this->job_assignment_id = $job_assignment_id;
    }

    /**
     * Factory function to create from a user id (results in a null job assignment id).
     *
     * @param int $user_id
     * @return static
     */
    public static function create_from_user_id(int $user_id): self {
        return new static($user_id, null);
    }

    /**
     * Factory function to create from a job assignment row (stdClass).
     *
     * @param stdClass $job_assignment
     * @return static
     */
    public static function create_from_job_assignment(stdClass $job_assignment): self {
        return new static($job_assignment->userid, $job_assignment->id);
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * Does he parameter set have a job assignment id.
     *
     * @return bool
     */
    public function has_job_assignment_id(): bool {
        return $this->job_assignment_id !== null;
    }

    /**
     * @return int|null
     */
    public function get_job_assignment_id(): ?int {
        return $this->job_assignment_id;
    }

    /**
     * Get a key we can use later for searching this collection faster
     *
     * @return string
     */
    public function get_key(): string {
        return $this->user_id.'-'.$this->job_assignment_id;
    }

    /**
     * Do these assignment parameters match a track user assignment on user id and job assignment.
     *
     * @param track_user_assignment $track_user_assignment
     * @return bool
     */
    public function matches_track_user_assignment(track_user_assignment $track_user_assignment): bool {
        return $track_user_assignment->key === $this->get_key();
    }

}