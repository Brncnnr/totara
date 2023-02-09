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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

/**
 * Interface for elements that can display responses (not necessarily directly accept them from users).
 *
 * @package mod_perform\models\activity\helpers
 */
interface displays_responses {

    /**
     * Format a response into lines ready to be displayed.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string[]
     */
    public function format_response_lines(?string $encoded_response_data, ?string $encoded_element_data): array;

    /**
     * Pull the answer text string out of the encoded json data.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|string[]|null
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data);

    /**
     * This method return element's user form vue component name.
     *
     * @return string
     */
    public function get_participant_response_component(): string;

    /**
     * Return true if element response required enabled
     *
     * @return bool
     */
    public function is_response_required_enabled(): bool;

    /**
     * @return bool
     */
    public function is_title_required(): bool;

}
