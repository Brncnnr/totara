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
 * @package totara_userstatus
 */

namespace mod_perform\models\activity\helpers;

use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\models\response\section_element_response;
use mod_perform\state\subject_instance\closed;

class external_participant_token_validator {

    /**
     * @var string
     */
    protected $token;

    /**
     * @var participant_instance|null
     */
    protected $participant_instance;

    /**
     * @param string $token
     */
    public function __construct(string $token) {
        $this->token = $token;
        $this->participant_instance = $this->load_instance_by_token();
    }

    /**
     * Load a participant instance by given token
     *
     * @return participant_instance|null
     */
    protected function load_instance_by_token(): ?participant_instance {
        /** @var participant_instance_entity $participant_instance */
        $participant_instance = participant_instance_entity::repository()
            ->join([external_participant::TABLE, 'ep'], 'participant_id', 'id')
            ->where('participant_source', participant_source::EXTERNAL)
            ->where('ep.token', $this->token)
            ->one();

        return $participant_instance ? participant_instance::load_by_entity($participant_instance) : null;
    }

    /**
     * Get the participant instance associated with the token (if exists)
     *
     * @return participant_instance|null
     */
    public function get_participant_instance(): ?participant_instance {
        return $this->participant_instance;
    }

    /**
     * Returns true if the token is valid, implies that the subject instance is not closed
     *
     * @return bool
     */
    public function is_valid() {
        return $this->participant_instance !== null;
    }

    /**
     * Returns true of the subject instance is closed or the participant instance does not exist
     *
     * @return bool
     */
    public function is_subject_instance_closed(): bool {
        if ($this->participant_instance) {
            return $this->participant_instance->subject_instance->availability == closed::get_code();
        }
        return true;
    }

    /**
     * Returns true if the participant_instance of the given participant_section_id
     * matches with the participant_instance the token belongs to.
     *
     * @param int $participant_section_id
     * @return bool
     */
    public function is_valid_for_section(int $participant_section_id): bool {
        $participant_section = participant_section_model::load_by_id($participant_section_id);
        return $this->participant_instance && $participant_section->participant_instance_id == $this->participant_instance->id;
    }

    /**
     * Returns true if the specified element response can be viewed by the external user with this token.
     *
     * @param element_response $element_response
     * @return bool
     */
    public function is_valid_for_response(element_response $element_response): bool {
        return $this->is_valid()
            && section_element_response::can_participant_view_response($element_response, $this->participant_instance);
    }

    /**
     * Find an external participant token in the current session.
     * Checks the 'wantsurl' URL in the session for the token param.
     *
     * @return string|null
     */
    public static function find_token_in_session(): ?string {
        global $SESSION;

        $referer = $SESSION->wantsurl ?? $_SERVER['HTTP_REFERER'] ?? null;
        if (empty($referer)) {
            return null;
        }

        $matches = [];
        $found = preg_match("/token=([a-f0-9]{64})/", $referer, $matches);
        if ($found > 0 && !empty($matches[1])) {
            return $matches[1];
        }

        return null;
    }

}