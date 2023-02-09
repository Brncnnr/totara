<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace performelement_aggregation\observers;

use mod_perform\event\participant_section_progress_updated;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\state\participant_section\complete;
use performelement_aggregation\aggregation_response_calculator;

class participant_section_aggregation {

    /**
     * When progress status of a participant section is marked compete,
     * or re-submitted (compete) update aggregation/calculations responses
     *
     * @param participant_section_progress_updated $event
     */
    public static function update_aggregation_responses(participant_section_progress_updated $event): void {
        // Aggregation is only calculated for submitted sections
        $progress = $event->other['progress'];
        if ($progress !== complete::get_name()) {
            return;
        }

        /** @var participant_section_entity $source_participant_section */
        $source_participant_section = participant_section_entity::repository()->find_or_fail($event->objectid);
        aggregation_response_calculator::calculate_responses_effected_by($source_participant_section);
    }
}