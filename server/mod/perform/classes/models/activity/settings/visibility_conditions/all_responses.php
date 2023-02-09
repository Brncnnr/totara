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

namespace mod_perform\models\activity\settings\visibility_conditions;

use core\collection;
use mod_perform\models\activity\participant_instance;
use mod_perform\state\participant_instance\open;

/**
 * Class all_responses
 */
class all_responses extends visibility_option {

    public const VALUE = 2;

    /**
     * @inheritDoc
    */
    public function show_responses(participant_instance $participant_instance, collection $other_participant_instances): bool {
        // Note this is NOT open rather than closed, so we also exclude not_applicable states too.
        $participant_instance_open = $participant_instance->get_availability_state() instanceof open;
        $other_participant_instances_open = $other_participant_instances->has('availability', open::get_code());

        return !$participant_instance_open && !$other_participant_instances_open;
    }

    /**
     * @inheritDoc
     */
    public function get_value(): int {
        return self::VALUE;
    }

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return get_string('visibility_condition_all_participants_closed', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_participant_description(): ?string {
        return get_string('visibility_condition_all_participants_closed_description', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_view_only_participant_description(): ?string {
        return get_string('visibility_condition_all_participants_closed_view_only_description', 'mod_perform');
    }
}