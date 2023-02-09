<?php
/**
 * This file is part of Totara Learn
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\webapi\resolver\mutation;

use coding_exception;
use container_workspace\enrol\manager;
use container_workspace\webapi\middleware\require_workspace_access;
use container_workspace\workspace;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use core_container\factory;

/**
 * Handles removing a single audience from a workspace
 */
class remove_audience extends mutation_resolver {

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new require_workspace_access('input.workspace_id', ['can_add_audiences']),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $workspace_id = $input['workspace_id'];
        $audience_id = $input['audience_id'];

        if (empty($audience_id)) {
            throw new coding_exception('No audience_id provided');
        }

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        $enrol_manager = manager::from_workspace($workspace);
        $enrol_manager->unenrol_audience($audience_id);

        return ['result' => true];
    }
}