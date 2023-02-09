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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\activity;

use mod_perform\state\activity\condition\at_least_one_section_with_question_and_answering_relationship;
use mod_perform\state\activity\condition\at_least_one_track_with_one_assignment;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "draft" state status of an activity.
 *
 * @package mod_perform
 */
class draft extends activity_state {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return 'DRAFT';
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('activity_status_draft', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_transitions(): array {
        return [
            // A draft activity can be activated.
            transition::to(new active($this->object))->with_conditions([
                at_least_one_section_with_question_and_answering_relationship::class,
                at_least_one_track_with_one_assignment::class
            ]),
        ];
    }
}
