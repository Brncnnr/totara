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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\trigger\repeating;

use mod_perform\dates\date_offset;
use mod_perform\entity\activity\temp_track_user_assignment_queue;

/**
 * Determines when subject instances are to be repeatedly created for the same
 * activity when specific conditions are fulfilled.
 */
abstract class trigger {
    /**
     * Default constructor.
     */
    public function __construct() {
        // EMPTY BLOCK.
    }

    /**
     * Returns the trigger name.
     *
     * @return string the trigger name.
     */
    abstract public function get_name(): string;

    /**
     * Returns the trigger interval type.
     *
     * @return string the trigger interval type.
     */
    abstract public function get_interval(): string;

    /**
     * Subclass specific function to determine the timestamp to compare to when
     * determining if new subject instances should be generated.
     *
     * @param temp_track_user_assignment_queue $assignment reference activity
     *        assignments.
     *
     * @return ?int the reference Epoch date.
     */
    abstract public function get_reference_timestamp(
        temp_track_user_assignment_queue $assignment
    ): ?int;

    /**
     * Indicates whether the activity should be repeated at this time.
     *
     * @param temp_track_user_assignment_queue $assignment reference activity
     *        assignment.
     *
     * @return bool true if the activity should repeat at this time.
     */
    final public function should_trigger(
        temp_track_user_assignment_queue $assignment
    ): bool {
        $timestamp = $this->get_reference_timestamp($assignment);
        if (is_null($timestamp)) {
            // Not time to fire yet.
            return false;
        }

        $threshold = date_offset::create_from_json($assignment->track_repeating_offset)
            ->apply($timestamp);

        return time() > $threshold;
    }
}
