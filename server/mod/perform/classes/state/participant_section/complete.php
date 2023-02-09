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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\participant_section;

use mod_perform\state\participant_section\condition\all_answers_complete;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "complete" progress status of a participant section.
 *
 * @package mod_perform
 */
class complete extends participant_section_progress {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return 'COMPLETE';
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 20;
    }

    /**
     * @inheritDoc
     */
    public function get_transitions(): array {
        return [
            // The participant has saved a draft OR an admin has manually moved progress backwards.
            transition::to(new in_progress($this->object)),

            // Re-complete (triggering events again).
            transition::to(new complete($this->object))->with_conditions([
                all_answers_complete::class
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function complete(): void {
        // It could be that the section was already completed but the close on completion setting got changed.
        // In this case we still want to react.
        if ($this->can_switch(complete::class)) {
            $this->object->switch_state(complete::class);
        }
    }

    /**
     * @inheritDoc
     */
    public function on_participant_access(): void {
        // Not relevant when already complete. Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function manually_complete(): void {
        // Not relevant when already complete. Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function manually_uncomplete(): void {
        // The user must have done something for it to get into the "complete" state. We move back
        // to "in_progress" to force the user to submit the section again.
        $this->object->switch_state(in_progress::class);
    }
}
