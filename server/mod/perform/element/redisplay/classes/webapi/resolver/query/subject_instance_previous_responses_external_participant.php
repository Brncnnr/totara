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
 */

namespace performelement_redisplay\webapi\resolver\query;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use invalid_parameter_exception;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use mod_perform\models\response\participant_section as participant_section_model;
use performelement_redisplay\data_provider\previous_responses;
use mod_perform\models\activity\section_element_reference;

/**
 * Query to get previous responses for a section element on a subject instance
 * related to the current participant section's subject instance for an external participant.
 */
class subject_instance_previous_responses_external_participant extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $participant_section_id = $args['input']['participant_section_id'] ?? null;
        $section_element_id = $args['input']['section_element_id'] ?? null;
        $token = $args['input']['token'] ?? null;

        if (empty($participant_section_id) || empty($section_element_id) || empty($token)) {
            throw new invalid_parameter_exception('Missing mandatory arguments.');
        }

        $validator = new external_participant_token_validator($token);

        if (!$validator->is_valid() 
            || !section_element_reference::participant_section_can_access_section_element($participant_section_id, $section_element_id)
        ) {
            throw new coding_exception('Invalid access to redisplay');
        }

        $current_data = self::get_current_data($participant_section_id);
        // Make sure the instance the token is valid for and the one for the given participant_section matches
        if ($validator->get_participant_instance()->id !== $current_data['participant_instance']->id) {
            throw new coding_exception('Participant instances for given token and participant_section do not match');
        }
        
        $ec->set_relevant_context($current_data['activity']->get_context());

        $previous_responses_provider = new previous_responses($section_element_id, $current_data['subject_instance']);

        return $previous_responses_provider->build($current_data);
    }

    /**
     * Get current activity data based on participant section id.
     *
     * @param int $participant_section_id
     * @return array
     */
    private static function get_current_data(int $participant_section_id): array {
        $data = [];
        $participant_section = participant_section_model::load_by_id($participant_section_id);
        $data['subject_instance'] = $participant_section->participant_instance->subject_instance;
        $data['participant_instance'] = $participant_section->get_participant_instance();
        $data['activity'] = $participant_section->participant_instance->subject_instance->activity;

        return $data;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
        ];
    }
}