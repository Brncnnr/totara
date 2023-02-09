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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use mod_perform\data_providers\response\participant_section as participant_section_provider;
use mod_perform\data_providers\response\participant_section_with_responses;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use mod_perform\models\activity\participant_source;

class participant_section_external_participant extends participant_section {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        // This query is supposed to work only for non-logged in external users
        global $USER;
        if ($USER && $USER->id > 0) {
            return null;
        }

        $participant_instance_id = $args['participant_instance_id'] ?? 0;
        $participant_section_id = $args['participant_section_id'] ?? 0;

        self::check_required_args($participant_instance_id, $participant_section_id);

        $token = $args['token'] ?? null;
        if (empty($token)) {
            return null;
        }

        $validator = new external_participant_token_validator($token);
        if (!$validator->is_valid() || $validator->is_subject_instance_closed()) {
            return null;
        }

        $participant_instance = $validator->get_participant_instance();
        if ($participant_instance_id && $participant_instance_id != $participant_instance->id) {
            return null;
        }

        if ($participant_instance->should_be_hidden()) {
            return null;
        }

        $participant_id = $participant_instance->participant_id;

        $section_provider = new participant_section_provider($participant_id, participant_source::EXTERNAL);
        $participant_section = $participant_section_id
            ? $section_provider->find_by_section_id($participant_section_id)
            : $section_provider->find_by_instance_id($participant_instance_id);

        // Just making sure we have found a section and it matches the instance
        if (!$participant_section || $participant_section->participant_instance_id != $participant_instance->id) {
            return null;
        }

        $ec->set_relevant_context($participant_instance->get_context());

        $data_provider = new participant_section_with_responses($participant_section);

        // When this participant section is fetched, for now we can safely assume that the participant is accessing
        // the section. In the future, this may have to move to a set_accessed mutation, e.g. if sections become
        // expandable in the front end and are fetched before actually being accessed.
        $participant_section->on_participant_access();

        return $data_provider->build();
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