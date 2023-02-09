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
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use mod_perform\hook\pre_section_element_deleted;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class element_deletion_validation  extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec): array {
        $source_section_element_id = $args['input']['section_element_id'];

        $hook = new pre_section_element_deleted($source_section_element_id);
        $hook->execute();

        $description = null;
        $data = null;

        if (!empty($hook->get_reasons())) {
            $first_reason = $hook->get_first_reason();
            $description = $first_reason->get_description();
            $data = $first_reason->get_data();
        }

        return [
            'title' => get_string('modal_can_not_delete_element_title', 'mod_perform'),
            'can_delete' => $hook->can_delete(),
            'reason' => [
                "description" => $description,
                "data" => $data
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login(),
            require_activity::by_section_element_id('input.section_element_id', true),
            require_manage_capability::class,
        ];
    }
}
