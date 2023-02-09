<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use mod_perform\data_providers\activity\subject_instance_for_participant as subject_instance_data_provider;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;

class subject_instance_for_external_participant extends query_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        // This query is supposed to work only for non-logged in external users
        global $USER;
        if ($USER && $USER->id > 0) {
            return null;
        }

        $subject_instance_id = $args['subject_instance_id'] ?? 0;
        if (!$subject_instance_id) {
            return null;
        }

        $token = $args['token'] ?? 0;
        if (empty($token)) {
            return null;
        }

        $validator = new external_participant_token_validator($token);
        if (!$validator->is_valid() || $validator->is_subject_instance_closed()) {
            return null;
        }

        // Validate that the subject instance matches with the one for the token
        $participant_instance = $validator->get_participant_instance();
        if ($participant_instance->subject_instance_id != $subject_instance_id) {
            return null;
        }

        $ec->set_relevant_context($participant_instance->get_context());

        $participant_id = $participant_instance->participant_id;

        /** @var subject_instance_model $subject_instance */
        return (new subject_instance_data_provider($participant_id, participant_source::EXTERNAL))
            ->get_subject_instance($subject_instance_id);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
        ];
    }
}