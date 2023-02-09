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

use container_workspace\discussion\discussion;
use container_workspace\discussion\discussion_helper;
use container_workspace\local\workspace_helper;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;

/**
 * Mutation for deleting the discussion within workspace
 */
class delete_discussion extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $USER;

        $discussion = discussion::from_id($args['id']);
        if (!$ec->has_relevant_context()) {
            $workspace_id = $discussion->get_workspace_id();
            $context = \context_course::instance($workspace_id);

            $ec->set_relevant_context($context);
        }

        discussion_helper::delete_discussion($discussion, $USER->id);

        // Update the timestamp of the workspace.
        $workspace = $discussion->get_workspace();
        workspace_helper::update_workspace_timestamp($workspace, $USER->id);

        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace')
        ];
    }
}