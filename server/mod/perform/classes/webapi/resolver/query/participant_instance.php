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
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\util;

class participant_instance extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $participant_instance_id = $args['participant_instance_id'];

        $participant_instance = participant_instance_model::load_by_id($participant_instance_id);

        if (!self::can_view($participant_instance)) {
            return null;
        }

        $ec->set_relevant_context($participant_instance->get_subject_instance()->get_context());

        return $participant_instance;
    }

    private static function can_view(participant_instance_model $participant_instance): bool {
        if ($participant_instance->get_is_for_current_user()) {
            return true;
        }

        return util::can_manage_participation(
            user::logged_in()->id,
            $participant_instance->get_subject_instance()->subject_user_id
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login(),
        ];
    }
}