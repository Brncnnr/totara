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
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use mod_perform\models\activity\section_element_reference;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\util;
use performelement_redisplay\data_provider\previous_responses;

/**
 * Query to get previous responses for a section element on a subject instance
 * related to the current participant section's subject instance for a logged-in user.
 */
class subject_instance_previous_responses extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $subject_instance_id = $args['input']['subject_instance_id'];
        $participant_section_id = $args['input']['participant_section_id'] ?? null;
        $section_element_id = $args['input']['section_element_id'];

        $current_data = self::get_current_data($subject_instance_id);
        $ec->set_relevant_context($current_data['activity']->get_context());

        if ($participant_section_id !== null) {
            $current_data['participant_instance'] = participant_section_model::load_by_id($participant_section_id)->get_participant_instance();
            // If the section does not belong to the same subject instance or the section element is not in the same activity
            if ($current_data['participant_instance']->subject_instance_id != $subject_instance_id
                || !section_element_reference::participant_section_can_access_section_element($participant_section_id, $section_element_id)
            ) {
                throw new coding_exception('Invalid access to redisplay');
            }
        } else {
            $viewing_user = user::logged_in();
            if (!util::can_report_on_user($current_data['subject_instance']->subject_user_id, $viewing_user->id)) {
                throw new coding_exception('Invalid report access to redisplay');
            }
        }

        $previous_responses_provider = new previous_responses($section_element_id, $current_data['subject_instance']);

        return $previous_responses_provider->build($current_data);
    }

    /**
     * Get current activity data based on subject instance id.
     *
     * @param int $subject_instance_id
     * @return array
     */
    private static function get_current_data(int $subject_instance_id): array {
        $data = [];
        $subject_instance = subject_instance::load_by_id($subject_instance_id);
        $data['subject_instance'] = $subject_instance;
        $data['activity'] = $subject_instance->activity;

        return $data;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login(),
        ];
    }
}