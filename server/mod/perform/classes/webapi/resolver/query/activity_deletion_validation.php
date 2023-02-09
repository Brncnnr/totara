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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use mod_perform\hook\dto\pre_deleted_warning_dto;
use mod_perform\hook\pre_activity_deleted;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class activity_deletion_validation extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $source_activity_id = $args['input']['activity_id'];
        $hook = new pre_activity_deleted($source_activity_id);
        $hook->execute();

        $description = null;
        $data = null;

        if ($first_reason = $hook->get_first_reason()) {
            $description = $first_reason->get_description();
            $data = $first_reason->get_data();
        }

        $warnings = array_map(static function(pre_deleted_warning_dto $warning) {
            return [
                'description' => $warning->get_description(),
                'items' => $warning->get_items(),
            ];
        }, $hook->get_warnings());

        $can_delete = $hook->can_delete();

        return [
            'title' => $can_delete ? '' : get_string('modal_can_not_delete_activity_title', 'mod_perform'),
            'can_delete' => $can_delete,
            'reason' => [
                "description" => $description,
                "data" => $data
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_activity_id('input.activity_id', true),
            require_manage_capability::class
        ];
    }
}
