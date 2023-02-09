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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\mutation;

use container_workspace\event\member_leave;
use container_workspace\member\member;
use container_workspace\webapi\middleware\require_login_workspace;
use container_workspace\webapi\middleware\workspace_availability_check;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use core_container\factory;

/**
 * Mutation for user to leave a workspace
 */
class leave extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return member
     */
    public static function resolve(array $args, execution_context $ec): member {
        global $USER;

        $workspace = factory::from_id($args['workspace_id']);

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        $member = member::from_user($USER->id, $workspace->get_id());

        if ($member->get_added_via_group()) {
            throw new \totara_webapi\client_aware_exception(
                new \moodle_exception('cant_leave', 'container_workspace'),
                ['category' => 'container_workspace/leave_fail_group']
            );
        }

        $member->leave($USER->id);

        // Trigger event.
        $event = member_leave::from_member($member);
        $event->trigger();

        return $member;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login_workspace('workspace_id'),
            new require_advanced_feature('container_workspace'),
            new workspace_availability_check('workspace_id')
        ];
    }

}