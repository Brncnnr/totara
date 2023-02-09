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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\hook;

use mod_perform\models\activity\element;
use mod_perform\models\activity\participant_instance;
use totara_core\hook\base;

/**
 * This hook allows special handling when saving and processing the response data for an element.
 *
 * @package mod_perform\hook
 */
class post_element_response_submission extends base {

    /**
     * @var int
     */
    private $response_id;

    /**
     * @var string
     */
    private $response_data;

    /**
     * @var element
     */
    private $element;

    /**
     * @var participant_instance
     */
    private $participant_instance;

    public function __construct(int $response_id, element $element, participant_instance $participant_instance,?string $response_data) {
        $this->response_id = $response_id;
        $this->element = $element;
        $this->participant_instance = $participant_instance;
        $this->response_data = $response_data;
    }

    /**
     * Get the response id.
     *
     * @return int
     */
    public function get_response_id(): int {
        return $this->response_id;
    }

    /**
     * Get the response data.
     *
     * @return string|null
     */
    public function get_response_data(): ?string {
        return $this->response_data;
    }

    /**
     * Set the response data.
     *
     * @param string|null $response_data
     */
    public function set_response_data(?string $response_data): void {
        $this->response_data = $response_data;
    }

    /**
     * Get the element.
     *
     * @return element
     */
    public function get_element(): element {
        return $this->element;
    }

    /**
     * Checks if it's the same element.
     *
     * @param string
     * @return bool
     */
    public function matches_element_plugin(string $plugin_class): bool {
        $element_plugin = $this->element->get_element_plugin();

        return $element_plugin instanceof $plugin_class;
    }

    /**
     * Get the participant instance.
     *
     * @return participant_instance
     */
    public function get_participant_instance(): participant_instance {
        return $this->participant_instance;
    }

}
