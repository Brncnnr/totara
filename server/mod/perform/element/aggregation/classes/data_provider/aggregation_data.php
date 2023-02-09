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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 */

namespace performelement_aggregation\data_provider;

use core\collection;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\data_providers\activity\sections as sections_provider;
use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;
use mod_perform\entity\activity\element;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\section;
use performelement_aggregation\aggregation;

/**
 * Data provider class that adds extra information into the reference elements JSON data.
 */
class aggregation_data {

    /**
     * @string The serialized key for all possible aggregatable sections.
     */
    public const AGGREGATABLE_SECTIONS = 'aggregatableSections';

    /**
     * @var array Keyed by activity id
     */
    public static $aggregatable_section_cache = [];

    /**
     * Add the source section element ids back into the json element data/settings.
     *
     * @param element $element
     * @return array
     */
    public function include_extra_info(element $element): array {
        $extra = [
            self::AGGREGATABLE_SECTIONS => $this->get_aggregatable_sections($element),
            aggregation::SOURCE_SECTION_ELEMENT_IDS => $this->get_source_section_element_ids($element->id),
        ];

        $original = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        return array_merge($original, $extra);
    }

    /**
     * @param element $element
     * @return collection|section[]
     */
    private function get_aggregatable_sections(element $element): array {
        $activity_id = $element->section_element->section->activity_id;

        if (array_key_exists($activity_id, static::$aggregatable_section_cache)) {
            return static::$aggregatable_section_cache[$activity_id];
        }

        $aggregatable_sections = (new sections_provider())->get_sections_with_aggregatable_section_elements($activity_id);

        $activity = activity::load_by_entity($element->section_element->section->activity);
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $activity->get_context());

        $result = [];
        foreach ($aggregatable_sections as $aggregatable_section) {
            $section_elements = [];
            foreach ($aggregatable_section->aggregatable_section_elements as $aggregatable_section_element) {
                $plugin = element_plugin::load_by_plugin($aggregatable_section_element->element->plugin_name);
                $section_elements[] = [
                    'id' => $aggregatable_section_element->id,
                    'element' => [
                        'title' => $formatter->format($aggregatable_section_element->element->title),
                        'element_plugin' => [
                            'name' => $plugin->get_name()
                        ]
                    ]
                ];
            }
            $result[] = [
                'id' => $aggregatable_section->id,
                'title' => $formatter->format($aggregatable_section->title),
                'aggregatable_section_elements' => $section_elements
            ];
        }

        static::$aggregatable_section_cache[$activity_id] = $result;

        return $result;
    }

    /**
     * @param int $aggregation_element_id
     * @return int[]
     */
    private function get_source_section_element_ids(int $aggregation_element_id): array {
        /** @var section_element_reference_entity[] $section_element_reference */
        $section_element_references = section_element_reference_entity::repository()
            ->where('referencing_element_id', $aggregation_element_id)
            ->order_by('id') // Keep display order consistent with insert order.
            ->get();

        if (count($section_element_references) === 0) {
            return [];
        }

        return $section_element_references->pluck('source_section_element_id');
    }

}
