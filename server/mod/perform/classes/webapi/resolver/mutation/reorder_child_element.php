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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\element;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class reorder_child_element extends mutation_resolver {

    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $child_element = element::load_by_id($input['element_id']);
        $parent_element = $child_element->get_parent_element();

        if (is_null($parent_element)) {
            throw new coding_exception("Can't reorder element without a parent.");
        }

        $parent_element->get_child_element_manager()->reorder_child_element_to_after($input['element_id'], $input['after_sibling_element_id']);

        return [
            'parent_element' => $parent_element,
        ];
    }

    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_element_id('input.element_id'),
            require_manage_capability::class,
        ];
    }

}