<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class update_section_elements extends mutation_resolver {
    /**
     * This updates the list of relationships for a specified section.
     *
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        debugging('This mutation has been deprecated. Use the individual section element mutations.', DEBUG_DEVELOPER);
        $section_form_data = $args['input'];

        $section = section::load_by_id($section_form_data['section_id']);

        $errors = static::validate_inputs($section_form_data);
        if ($errors) {
            throw new \coding_exception(implode(', ', $errors));
        }

        builder::get_db()->transaction(function () use ($section, $section_form_data) {
            // Remove elements from the section.
            $delete_section_elements = [];
            foreach ($section_form_data['delete'] ?? [] as $delete_section_element_form_data) {
                $delete_section_elements[] = section_element::load_by_id($delete_section_element_form_data['section_element_id']);
            }
            $section->remove_section_elements($delete_section_elements);

            // Keep a track of where each element will be when we are finished.
            $sort_orders = [];

            // Create new elements and add them to this section.
            if (!empty($section_form_data['create_new'])) {
                $context = $section->get_activity()->get_context();

                foreach ($section_form_data['create_new'] as $create_new_form_data) {
                    $element = element::create(
                        $context,
                        $create_new_form_data['plugin_name'],
                        $create_new_form_data['title'],
                        $create_new_form_data['identifier'],
                        $create_new_form_data['data'] ?? null,
                        $create_new_form_data['is_required'] ?? null
                    );
                    $sort_orders[$create_new_form_data['sort_order']] = $section->add_element($element);
                }
            }

            // Link existing elements to this section.
            foreach ($section_form_data['create_link'] ?? [] as $create_link_form_data) {
                $element = element::load_by_id($create_link_form_data['element_id']);
                $sort_orders[$create_link_form_data['sort_order']] = $section->add_element($element);
            }

            // Move elements within this section.
            foreach ($section_form_data['move'] ?? [] as $move_form_data) {
                $sort_orders[$move_form_data['sort_order']] = section_element::load_by_id($move_form_data['section_element_id']);
            }
            $section->move_section_elements($sort_orders);

            // Update existing elements.
            foreach ($section_form_data['update'] ?? [] as $update_form_data) {
                $element = element::load_by_id($update_form_data['element_id']);
                $element->update_details(
                    $update_form_data['title'],
                    $update_form_data['data'],
                    $update_form_data['is_required'] ?? null,
                    $update_form_data['identifier'] ?? ''
                );
            }
        });

        return [
            'section' => section::load_by_id($section->id)
        ];
    }

    /**
     * @param array $section_form_data
     * @return array
     */
    private static function validate_inputs(array $section_form_data): array {
        $errors = [];
        return $errors;
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