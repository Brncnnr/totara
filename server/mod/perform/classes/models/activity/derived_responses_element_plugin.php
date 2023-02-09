<?php
/*
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

namespace mod_perform\models\activity;

use mod_perform\models\activity\helpers\displays_responses;

/**
 * Class derived_responses_element_plugin
 *
 * Base class for defining elements that derive or calculate the responses
 * rather than accepting them from the users directly.
 *
 * @package mod_perform\models\activity
 */
abstract class derived_responses_element_plugin extends element_plugin implements displays_responses {

    /**
     * Format a response into lines ready to be displayed.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string[]
     */
    abstract public function format_response_lines(?string $encoded_response_data, ?string $encoded_element_data): array;

    /**
     * This method return element's user form vue component name.
     *
     * @return string
     */
    public function get_participant_response_component(): string {
        return 'mod_perform/components/element/participant_form/ResponseDisplay';
    }

    /**
     * @inheritDoc
     */
    public function get_participant_print_component(): string {
        return $this->get_participant_form_component();
    }

    /**
     * @inheritDoc
     */
    public function has_title(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_title_text(): string {
        return get_string('element_title', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function is_title_required(): bool {
        return true;
    }

    /**
     * Return true if element has reporting id
     *
     * @return bool
     */
    public function has_reporting_id(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function is_response_required_enabled(): bool {
        return false;
    }

}
