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

use container_workspace\webapi\middleware\workspace_availability_check;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use container_workspace\member\member;
use core_container\factory;
use container_workspace\workspace;

/**
 * Class join
 * @package container_workspace\webapi\resolver\mutation
 */
class join extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return member
     */
    public static function resolve(array $args, execution_context $ec): member {
        global $USER;

        /** @var workspace $workspace */
        $workspace = factory::from_id($args['workspace_id']);

        if (!$ec->has_relevant_context()) {
            $context = $workspace->get_context();
            $ec->set_relevant_context($context);
        }

        $user = \core_user::get_user($USER->id);

        if ($user->deleted || $user->suspended) {
            throw new \coding_exception("User had been deleted or suspended");
        }

        return member::join_workspace($workspace, $USER->id);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new workspace_availability_check('workspace_id')
        ];
    }

}