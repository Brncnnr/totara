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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use coding_exception;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity;
use mod_perform\task\service\participant_instance_creation;
use mod_perform\util;
use mod_perform\webapi\middleware\require_activity;

class add_participants extends mutation_resolver {

    /**
     * This adds new participants to the activity
     *
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        // The require_activity middleware loads the activity and passes it along via the args
        /** @var activity $activity */
        $activity = $args['activity'];

        $args = $args['input'];

        if (!$activity->is_active()) {
            throw new coding_exception('Can only add participants to an active activity.');
        }

        self::check_capabilities($args);

        $added_participant_instances = (new participant_instance_creation())->add_instances(
            $args['subject_instance_ids'],
            $args['participants']
        );

        return ['participant_instances' => $added_participant_instances];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_subject_instance_ids('input.subject_instance_ids', true),
        ];
    }

    /**
     * Validate sufficient capabilities.
     *
     * @param array $args
     */
    private static function check_capabilities(array $args): void {
        if (util::has_manage_all_participants_capability(user::logged_in()->id)) {
            return;
        }

        /** @var subject_instance[] $subject_instances */
        $subject_instances = subject_instance::repository()
            ->where_in('id', $args['subject_instance_ids'])
            ->get();

        foreach ($subject_instances as $subject_instance) {
            require_capability(
                'mod/perform:manage_subject_user_participation',
                \context_user::instance($subject_instance->subject_user_id)
            );
        }
    }
}