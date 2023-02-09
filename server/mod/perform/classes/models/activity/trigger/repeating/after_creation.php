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

use mod_perform\entity\activity\temp_track_user_assignment_queue;

/**
 * Indicates whether to create a new subject instance for the same activity with
 * respect to the creation time of the previous instance.
 */
final class after_creation extends trigger {
    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'CREATION';
    }

    /**
     * {@inheritDoc}
     */
    public function get_interval(): string {
        return 'TIME_SINCE';
    }

    /**
     * {@inheritDoc}
     */
    public function get_reference_timestamp(
        temp_track_user_assignment_queue $assignment
    ): ?int {
        return $assignment->last_instance_created_at;
    }
}
