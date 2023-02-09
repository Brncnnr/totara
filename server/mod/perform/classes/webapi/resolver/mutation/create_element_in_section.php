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
 */

namespace mod_perform\webapi\resolver\mutation;

use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use invalid_parameter_exception;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class create_element_in_section extends mutation_resolver {

    public static function resolve(array $args, execution_context $ec) {
        $after_section_element_id = $args['input']['after_section_element_id'] ?? null;
        $element_data = $args['input']['element'];
        self::validate_element_data($element_data);
        $section = section::load_by_id($args['input']['section_id']);

        builder::get_db()->transaction(function () use ($element_data, $section, $after_section_element_id) {
            $element = element::create(
                $section->activity->get_context(),
                $element_data['plugin_name'],
                $element_data['element_details']['title'],
                $element_data['element_details']['identifier'] ?? '',
                $element_data['element_details']['data'] ?? null,
                $element_data['element_details']['is_required'] ?? null
            );
            $section->get_section_element_manager()->add_element_after($element, $after_section_element_id);
        });

        return [
            'section' => section::load_by_id($section->id),
        ];
    }

    /**
     * @param array $element_data
     * @throws invalid_parameter_exception
     */
    private static function validate_element_data(array $element_data): void {
        if (!isset($element_data['element_details']['title'])) {
            throw new invalid_parameter_exception('title must be provided');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_section_id('input.section_id', true),
            require_manage_capability::class
        ];
    }
}