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
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use core\webapi\execution_context;
use mod_perform\models\activity\participant_instance;

/**
 * This helper can be used to determine whether the current request,
 * identified with the given execution context is an actual external participation.
 *
 * @package mod_perform\models\activity\helpers
 */
class external_participation {

    private $validator;

    public function __construct(execution_context $execution_context) {
        $token = $execution_context->get_resolve_info()->variableValues['token']
            ?? $execution_context->get_resolve_info()->variableValues['input']['token']
            ?? null;

        if ($token) {
            $this->validator = new external_participant_token_validator($token);
        }
    }

    public function is_external_participation(): bool {
        global $USER;

        return !($USER->id && $USER->id > 0)
            && $this->validator && $this->validator->is_valid();
    }

    /**
     * Returns true this request is an external participation and the given instances matches
     * the one from the external user
     *
     * @param participant_instance $participant_instance
     * @return bool
     */
    public function belongs_to(participant_instance $participant_instance) {
        if ($this->is_external_participation()) {
            return $this->validator->get_participant_instance()->id == $participant_instance->id;
        }
        return false;
    }

}