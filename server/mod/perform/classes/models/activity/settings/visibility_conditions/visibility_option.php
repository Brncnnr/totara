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
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_instance;

/**
 * Abstract class visibility_option
 *
 * @package mod_perform\models\activity\settings\visibility_conditions
 */
abstract class visibility_option {

    /**
     * Calculates if response should be shown.
     *
     * @param participant_instance $participant_instance
     * @param collection|participant_instance_entity[] $other_participant_instances
     *
     * @return bool
     */
    abstract public function show_responses(participant_instance $participant_instance, collection $other_participant_instances): bool;

    /**
     * Get the value of visibility option.
     *
     * @return int
     */
    abstract public function get_value(): int;

    /**
     * Get name of visibility option.
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Get participant description.
     *
     * @return string
     */
    abstract public function get_participant_description(): ?string;

    /**
     * Get a description of when responses are made visible
     * in the end user form where the reader is a view-only participant.
     *
     * @return string
     */
    abstract public function get_view_only_participant_description(): ?string;

    /**
     * Default visibility option when anonymous responses are enabled.
     *
     * @return bool
     */
    final public function default_anonymous_option(): bool {
        return $this instanceof all_responses;
    }
}