<?php
/**
 * This file is part of Totara Learn
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\watcher;

use core_role_potential_assignees_course_and_above;
use core_role\hook\core_role_potential_assignees_container;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\assignment\assignment as assignment_entity;
use totara_core\advanced_feature;

/**
 * Class core_role_container
 */
class core_role_container {

    /**
     * Get the potential assignees selector for a given context.
     *
     * @param core_role_potential_assignees_container $hook.
     */
    public static function get_potential_assignees(core_role_potential_assignees_container $hook): void {
        // Not our problem.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        $activity = null;
        $context = $hook->get_context();
        if ($context->contextlevel == CONTEXT_COURSE) {
            // Find or fail workflow by course id.
            $activity = workflow_entity::repository()
                ->where('course_id', '=', $context->instanceid)
                ->one();
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            // Check the module is an approval activity
            $activity = assignment_entity::repository()
                ->join(['course_modules', 'cm'], 'id', '=', 'cm.instance')
                ->join(['modules', 'm'], 'cm.module', '=', 'm.id')
                ->where('m.name', '=', 'approval')
                ->where('cm.id', '=', $context->instanceid)
                ->order_by('id')
                ->first();
        }
        if ($activity) {
            $hook->set_potential_user_selector(
                new core_role_potential_assignees_course_and_above(
                    $hook->get_control_name(),
                    $hook->get_options()
                )
            );
        }
    }
}