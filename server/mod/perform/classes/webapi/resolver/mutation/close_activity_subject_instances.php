<?php
/*
 * This file is part of Totara Perform
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\task\manager;
use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_advanced_feature;
use mod_perform\task\close_activity_subject_instances_task;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_participants_capability;

/**
 * Handles the "mod_perform_close_activity_subject_instances" GraphQL mutation.
 */
class close_activity_subject_instances extends mutation_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        // These values cannot be invalid because they are set by middleware
        // running before this method executes.
        $activity_id = $args['activity']->id;
        $user_id = $args[require_manage_participants_capability::TARGET_USER_ID];

        // Closing of subject instances is implemented as a background task as
        // there could be many instances to close and it may take too long to do
        // it synchronously.
        $task = close_activity_subject_instances_task::create($activity_id, $user_id);

        // Note the 'true' as the second parameter to the queue_adhoc_task();
        // this ensures the task targetting this activity is not queued multiple
        // times however many times this method is invoked.
        //
        // The method returns a boolean false *or an integer record id*. Hence
        // the cast.
        return (bool)manager::queue_adhoc_task($task, true);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_activity_id('input.activity_id', true),
            new require_manage_participants_capability()
        ];
    }
}
