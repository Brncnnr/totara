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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace performelement_redisplay\data_provider;

use coding_exception;
use context_system;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\activity\section_element as section_element_model;
use performelement_redisplay\redisplay;

/**
 * Data provider class that adds extra information into the redisplay JSON data.
*/
class redisplay_data {

    /**
     * Adds extra info to redisplay element data.
     *
     * @param int $redisplay_element_id
     * @return array
     * @throws coding_exception
     */
    public function include_extra_info(int $redisplay_element_id): array {
        $element_settings = [];

        /** @var section_element_reference_entity $section_element_reference */
        $section_element_reference = section_element_reference_entity::repository()
            ->where('referencing_element_id', $redisplay_element_id)->one();

        if ($section_element_reference === null) {
            return $element_settings;
        }

        $element_settings[redisplay::SOURCE_SECTION_ELEMENT_ID] = $section_element_reference->source_section_element_id;

        $section_element = section_element_model::load_by_entity($section_element_reference->source_section_element);
        $activity = $section_element->section->activity;
        $element = $section_element->element;
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());

        $element_settings['activityId'] = $activity->id;
        $element_settings['activityName'] = $formatter->format($activity->name);
        $element_settings['activityStatus'] = $activity->get_state_details()::get_display_name();
        $element_settings['elementTitle'] = $formatter->format($element->title);

        /** @var respondable_element_plugin $element_plugin*/
        $element_plugin = $element->get_element_plugin();

        if (!$element_plugin->get_is_respondable()) {
            throw new coding_exception('section element must be respondable');
        }
        $element_settings['elementPluginName'] = $element_plugin->get_name();
        $element_settings['elementPluginDisplayComponent'] = $element_plugin->get_participant_response_component();

        $relationships = $activity->anonymous_responses
            ? $this->get_anonymous_relationship_string()
            : $this->get_relationships($section_element->section->get_answering_section_relationships());
        $element_settings['relationships'] = $relationships;

        return $element_settings;
    }

    /**
     * Get relationship string for answerable section relationships.
     *
     * @param $section_relationships
     * @return string
     */
    private function get_relationships($section_relationships): string {
        $relationships = $section_relationships
            ->map(function ($section_relationship) {
                return $section_relationship->core_relationship;
            })
            ->sort('sort_order')
            ->pluck('name');

        if (empty($relationships)) {
            return get_string('no_responding_relationships', 'performelement_redisplay');
        }

        return get_string(
            'responses_from_relationships',
            'performelement_redisplay',
            (object) [
                'relationships' => implode(', ', $relationships)
            ]
        );
    }

    /**
     * Get relationship string for anonymized activity.
     *
     * @return string
     */
    private function get_anonymous_relationship_string(): string {
        return get_string('responses_from_anonymous_relationships', 'performelement_redisplay');
    }
}
