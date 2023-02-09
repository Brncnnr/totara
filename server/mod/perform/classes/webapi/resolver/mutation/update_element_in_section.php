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

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class update_element_in_section extends mutation_resolver {

    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        $element_details = $input['element_details'];
        $section_element = section_element_model::load_by_id($input['section_element_id']);

        $section_element->element->update_details(
            $element_details['title'],
            $element_details['data'] ?? null,
            $element_details['is_required'] ?? null,
            $element_details['identifier'] ?? '',
        );

        return [
            'section' => $section_element->section,
        ];
    }

    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_section_element_id('input.section_element_id', true),
            require_manage_capability::class,
        ];
    }
}