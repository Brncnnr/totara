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
namespace container_workspace\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use container_workspace\discussion\discussion;
use container_workspace\workspace;

/**
 * Query resolver for fetching draft ids.
 */
class discussion_draft_id extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return int
     */
    public static function resolve(array $args, execution_context $ec): int {
        global $CFG;

        require_once("{$CFG->dirroot}/lib/filelib.php");

        if (empty($args['id'])) {
            return file_get_unused_draft_itemid();
        }

        $discussion_id = $args['id'];
        $discussion = discussion::from_id($discussion_id);

        $context = \context_course::instance($discussion->get_workspace_id());
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($context);
        }

        $draft_id = null;
        file_prepare_draft_area(
            $draft_id,
            $context->id,
            workspace::get_type(),
            discussion::AREA,
            $discussion_id
        );

        return $draft_id;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
        ];
    }

}