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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\hook;

use mod_perform\entity\activity\element_response;
use mod_perform\models\activity\participant_instance;
use totara_core\hook\base;

/**
 * This hook allows whether the specified participant instance is allowed to view the specified response to be overridden.
 *
 * @package mod_perform\hook
 */
class element_response_visibility extends base {

    /**
     * @var element_response
     */
    private $element_response;

    /**
     * @var int|null
     */
    private $viewing_user_id;

    /**
     * @var participant_instance|null
     */
    private $viewing_participant_instance;

    /**
     * @var bool
     */
    private $can_view = false;

    private function __construct(element_response $element_response) {
        $this->element_response = $element_response;
    }

    /**
     * Construct hook for an (internal) user that is viewing the response.
     *
     * @param element_response $element_response
     * @param int $user_id
     * @return $this
     */
    public static function for_viewing_user(element_response $element_response, int $user_id): self {
        $hook = new static($element_response);
        $hook->viewing_user_id = $user_id;
        return $hook;
    }

    /**
     * Construct hook for a participant instance that is viewing the response.
     *
     * @param element_response $element_response
     * @param participant_instance $viewing_participant_instance
     * @return $this
     */
    public static function for_viewing_participant(
        element_response $element_response,
        participant_instance $viewing_participant_instance
    ): self {
        $hook = new static($element_response);
        $hook->viewing_participant_instance = $viewing_participant_instance;
        return $hook;
    }

    /**
     * @return element_response
     */
    public function get_element_response(): element_response {
        return $this->element_response;
    }

    /**
     * @return int|null
     */
    public function get_viewing_user_id(): ?int {
        return $this->viewing_user_id;
    }

    /**
     * @return participant_instance|null
     */
    public function get_viewing_participant_instance(): ?participant_instance {
        return $this->viewing_participant_instance;
    }

    /**
     * Set that the response can be viewed.
     */
    public function set_can_view(): void {
        $this->can_view = true;
    }

    /**
     * Can the response be viewed?
     *
     * @return bool
     */
    public function get_can_view(): bool {
        return $this->can_view;
    }

}
