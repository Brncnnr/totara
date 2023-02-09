<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package performelement_aggregation
 */

namespace performelement_aggregation;

use coding_exception;
use core\collection;
use core\orm\query\builder;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\activity\section_element;
use mod_perform\state\participant_section\complete;

class aggregation_response_calculator {

    /**
     * Recalculate all aggregate responses that will be effected by the source_participant_section.
     *
     * @param participant_section_entity $source_participant_section
     */
    public static function calculate_responses_effected_by(participant_section_entity $source_participant_section): void {
        /** @var section_element_reference_entity[]|collection $derived_responses_references */
        $derived_responses_references = section_element_reference_entity::repository()
            ->as('ref')
            ->join([section_element_entity::TABLE, 'source_section_element'], 'source_section_element.id', 'ref.source_section_element_id')
            ->join([element_entity::TABLE, 'referencing_element'], 'referencing_element.id', 'ref.referencing_element_id')
            ->where('source_section_element.section_id', $source_participant_section->section_id)
            ->where('referencing_element.plugin_name', aggregation::get_plugin_name())
            ->order_by('ref.referencing_element_id')
            ->get();

        if (count($derived_responses_references) === 0) {
            return;
        }

        /** @var section_element_reference_entity[]|collection $sibling_references */
        $sibling_references = section_element_reference_entity::repository()
            ->as('ref')
            ->where_in('referencing_element_id', $derived_responses_references->pluck('referencing_element_id'))
            ->get();

        if (count($sibling_references) === 0) {
            return;
        }

        $reference_to_source_map = [];
        foreach ($sibling_references as $sibling_reference) {
            $reference_to_source_map[$sibling_reference->referencing_element_id][] = $sibling_reference->source_section_element_id;
        }

        foreach ($reference_to_source_map as $referencing_element_id => $source_section_element_ids) {
            self::calculate_for_participant(
                $referencing_element_id,
                $source_participant_section->participant_instance_id,
                array_unique($source_section_element_ids)
            );
        }
    }

    /**
     * @param int $referencing_element_id
     * @param int $participant_instance_id
     * @param int[] $source_section_element_ids
     */
    protected static function calculate_for_participant(
        int $referencing_element_id,
        int $participant_instance_id,
        array $source_section_element_ids
    ): void {
        [$calculation_methods, $excluded_values] = static::get_calculation_methods_and_excluded_values($referencing_element_id);

        $to_aggregate = static::get_values_to_aggregate($participant_instance_id, $source_section_element_ids, $excluded_values);

        // It's possible that all responses were left blank, in this case we will not populate the aggregate response.
        // This results in "no_response_submitted" being displayed in the ui.
        if (count($to_aggregate) === 0) {
            static::remove_derived_response($referencing_element_id, $participant_instance_id);
            return;
        }

        $response = [];
        foreach ($calculation_methods as $method) {
            $response[$method] = calculation_method::load_by_method($method)->aggregate($to_aggregate);
        }

        static::save_derived_response($referencing_element_id, $participant_instance_id, $response);
    }

    /**
     * @param int $participant_instance_id
     * @param array $source_section_element_ids
     * @param float[] $excluded_values
     * @return float[]
     */
    private static function get_values_to_aggregate(
        int $participant_instance_id,
        array $source_section_element_ids,
        array $excluded_values
    ): array {
        /**
         * @var element_response_entity[]|collection $responses
         * @var section_element_entity[]|collection $source_section_elements
         */
        [$responses, $source_section_elements] = static::get_responses_and_source_section_elements($participant_instance_id, $source_section_element_ids);

        return $responses->map(function (element_response_entity $element_response) use ($source_section_elements) {
            /** @var section_element $source_section_element */
            $source_section_element = $source_section_elements->item($element_response->section_element_id);

            $plugin = $source_section_element->get_element()->get_element_plugin();

            if (!$plugin instanceof respondable_element_plugin) {
                throw new coding_exception('Non respondable plugin "' . $plugin::get_plugin_name() . '" can not calculated derived responses.');
            }

            return $plugin->get_aggregatable_value(
                $element_response->response_data,
                $source_section_element->get_element()->get_data()
            );
        })->filter(function (?float $aggregatable_value) use ($excluded_values) {
            // Unanswered questions are always excluded.
            if ($aggregatable_value === null) {
                return false;
            }

            // Remove any null entries, which we allow to be saved.
            $cleaned_excluded_values = array_filter($excluded_values, function ($excluded_value): bool {
                return is_numeric($excluded_value);
            });

            $excluded_values_as_floats = array_map(function (float $excluded_value): float {
                return $excluded_value;
            }, $cleaned_excluded_values);

            return !in_array($aggregatable_value, $excluded_values_as_floats, true);
        })->all();
    }

    private static function get_responses_and_source_section_elements(int $participant_instance_id, array $source_section_element_ids): array {
        $responses = element_response_entity::repository()
            ->as('r')
            ->join([section_element_entity::TABLE, 'se'], 'se.id', 'r.section_element_id')
            ->join([participant_section_entity::TABLE, 'ps'], function (builder $builder) use ($participant_instance_id) {
                $builder->where_field('ps.section_id', 'se.section_id')
                    ->where('ps.participant_instance_id', $participant_instance_id)
                    ->where('ps.progress', complete::get_code());
            })
            ->where('r.participant_instance_id', $participant_instance_id)
            ->where_in('r.section_element_id', $source_section_element_ids)
            ->get();

        $source_section_elements = section_element_entity::repository()
            ->where_in('id', $source_section_element_ids)
            ->with('element')
            ->get()
            ->map_to(section_element::class)
            ->key_by('id');

        return [$responses, $source_section_elements];
    }

    /**
     * @param int $referencing_element_id
     * @return array[]
     */
    private static function get_calculation_methods_and_excluded_values(int $referencing_element_id): array {
        $derived_element = element::load_by_id($referencing_element_id);
        $data = json_decode($derived_element->get_data(), true, 512, JSON_THROW_ON_ERROR);

        return [$data[aggregation::CALCULATIONS], $data[aggregation::EXCLUDED_VALUES]];
    }

    private static function remove_derived_response(int $referencing_element_id, int $participant_instance_id): void {
        static::save_derived_response($referencing_element_id, $participant_instance_id, null);
    }

    private static function save_derived_response(int $referencing_element_id, int $participant_instance_id, ?array $response_data): void {
        $referencing_section_element_ids = builder::table(section_element_entity::TABLE)
            ->select('id')
            ->where('element_id', $referencing_element_id)
            ->get()
            ->pluck('id');

        $existing_aggregated_responses = $aggregated_response = element_response_entity::repository()
            ->where('participant_instance_id', $participant_instance_id)
            ->where_in('section_element_id', $referencing_section_element_ids)
            ->get();

        foreach ($referencing_section_element_ids as $referencing_section_element_id) {
            /** @var element_response_entity|null $aggregated_response */
            $aggregated_response = $existing_aggregated_responses->find(
                function (element_response_entity $existing_response) use ($participant_instance_id, $referencing_section_element_id) {
                    return (int) $existing_response->participant_instance_id === $participant_instance_id &&
                        (int) $existing_response->section_element_id === (int) $referencing_section_element_id;
                });

            // If the response is now empty, and the entity already exists, delete it.
            if ($response_data === null) {
                if ($aggregated_response !== null) {
                    $aggregated_response->delete();
                }
                continue;
            }

            if ($aggregated_response === null) {
                $aggregated_response = new element_response_entity();
                $aggregated_response->participant_instance_id = $participant_instance_id;
                $aggregated_response->section_element_id = $referencing_section_element_id;
            }

            $aggregated_response->response_data = json_encode($response_data, JSON_THROW_ON_ERROR);
            $aggregated_response->save();
        }
    }

}