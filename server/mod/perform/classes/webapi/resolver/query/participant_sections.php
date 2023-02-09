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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use mod_perform\data_providers\response\participant_section_with_responses;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\response\participant_section;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use moodle_exception;

class participant_sections extends query_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        /** @var user $user_id */
        $user_id = user::logged_in();

        $participant_sections = participant_section_entity::repository()::get_all_for_participant_instance(
            $args['participant_instance_id'],
            $user_id->id
        );

        if ($participant_sections->count() === 0) {
            return [];
        }

        /** @var participant_section_entity $first_participant_section */
        $first_participant_section = $participant_sections->first();
        $participant_instance = new participant_instance($first_participant_section->participant_instance);

        // Block access if the subject user got deleted.
        // Technically we shouldn't hit this for participants because that
        // part of the check is backed into get_all_for_participant_instance
        // and you can only get sections for the logged in user.
        if ($participant_instance->should_be_hidden()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($participant_instance->get_context());
        }

        return $participant_sections->map(function (participant_section_entity $participant_section) {
            return (new participant_section_with_responses(new participant_section($participant_section)))->build();
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login()
        ];
    }
}