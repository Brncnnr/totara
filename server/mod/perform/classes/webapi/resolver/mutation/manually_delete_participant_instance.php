<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use mod_perform\util;
use mod_perform\models\activity\participant_instance;
use mod_perform\webapi\middleware\require_activity;

class manually_delete_participant_instance extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $participant_instance = participant_instance::load_by_id($input['participant_instance_id']);

        $manager_id = user::logged_in()->id;
        $subject_user_id = $participant_instance->subject_instance->subject_user_id;
        if (!util::can_manage_participation($manager_id, $subject_user_id)) {
            throw new \coding_exception('You do not have permission to manage participation of the subject');
        }

        $participant_instance->manually_delete();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_authenticated_user(),
            require_activity::by_participant_instance_id('input.participant_instance_id', true)
        ];
    }
}