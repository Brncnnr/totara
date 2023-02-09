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
namespace container_workspace\totara_reaction\resolver;

use container_workspace\discussion\discussion;
use totara_reaction\resolver\base_resolver;
use container_workspace\interactor\discussion\interactor as discussion_interactor;
use container_workspace\interactor\workspace\interactor as workspace_interactor;

/**
 * This is mainly for the discussion within the workspace
 */
final class workspace_reaction_resolver extends base_resolver {
    /**
     * @param int $instance_id
     * @param string $area
     *
     * @return \context
     */
    public function get_context(int $instance_id, string $area): \context {
        global $DB;

        if (discussion::AREA == $area) {
            $workspace_id = $DB->get_field('workspace_discussion', 'course_id', ['id' => $instance_id]);
            return \context_course::instance($workspace_id);
        }

        throw new \coding_exception("The area '{$area}' is not supported");
    }

    /**
     * @param int $instance_id
     * @param int $user_id
     * @param string|null $area
     *
     * @return bool
     */
    public function can_create_reaction(int $instance_id, int $user_id, string $area): bool {
        if (discussion::AREA === $area) {
            // If the area is a discussion area, then the $instance_id will be the discussion's id.
            $interactor = discussion_interactor::from_discussion_id($instance_id);
            return $interactor->can_react();
        }

        throw new \coding_exception("Invalid area passed into the resolver: '${area}'");
    }

    /**
     * @param int       $instance_id
     * @param int       $user_id
     * @param string    $area
     *
     * @return bool
     */
    public function can_view_reactions(int $instance_id, int $user_id, string $area): bool {
        if (discussion::AREA === $area) {
            $discussion = discussion::from_id($instance_id);
            $workspace = $discussion->get_workspace();

            $workspace_interactor = new workspace_interactor($workspace, $user_id);
            return $workspace_interactor->can_view_discussions();
        }

        throw new \coding_exception("Invalid area passed into the resolver: {$area}");
    }
}