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

namespace mod_perform\state\activity;

use mod_perform\state\activity\condition\at_least_one_track_with_one_assignment;
use mod_perform\state\state;

abstract class activity_state extends state {

    /**
     * @inheritDoc
     */
    public static function get_type(): string {
        return 'status';
    }

    /**
     * Can this activity be potentially activated, checking if the transition
     * to active is possible
     *
     * @return bool
     */
    public function can_potentially_activate(): bool {
        return $this->get_transition_to(active::class) ? true : false;
    }

    /**
     * Can this activity be activated. This checks the status and the conditions
     * to activate an activity. This does not check the users capability to activate
     * this activity.
     *
     * @see at_least_one_track_with_one_assignment
     * @see at_least_one_section_with_question_and_answering_relationship
     *
     * @return bool
     */
    public function can_activate(): bool {
        return $this->can_potentially_activate() && $this->can_switch(active::class);
    }

    /**
     * Activate this activity if possible
     *
     * @return bool
     */
    public function activate(): bool {
        if ($this->can_activate()) {
            $this->object->switch_state(active::class);
            return true;
        }
        return false;
    }

}