<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\mutation;

use core\entity\user;
use core\orm\collection;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\helpers\capability_helper;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\assignment_actions;
use totara_competency\user_groups;

/**
 * Mutation to create a job assignment.
 */
class create_user_assignments extends mutation_resolver {

    /**
     * Creates an assignment and returns the new assignment id.
     *
     * @param array $args
     *
     * @param execution_context $ec
     * @return collection|assignment_model[]
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB;

        $user_id = (int)$args['user_id'];
        $competency_ids = $args['competency_ids'];
        $user_groups = [user_groups::USER => [$user_id]];

        capability_helper::require_can_assign($user_id);

        $assignments = (new assignment_actions())
            ->create_from_competencies(
                $competency_ids,
                $user_groups,
                self::get_type($user_id),
                assignment::STATUS_ACTIVE
            );

        $assignment_ids = [];
        foreach ($assignments as $assignment) {
            $assignment_ids[] = $assignment->get_id();
        }

        // In this case we don't want to wait for an adhoc or scheduled task to execute
        $task = new expand_task($DB);
        $task->expand_multiple($assignment_ids);

        return $assignments;
    }

    protected static function get_type(int $user_id): string {
        return $user_id == user::logged_in()->id
            ? assignment::TYPE_SELF
            : assignment::TYPE_OTHER;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competency_assignment'),
        ];
    }

}
