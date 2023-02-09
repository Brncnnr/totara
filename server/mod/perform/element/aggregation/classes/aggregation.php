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
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\models\activity\derived_responses_element_plugin;
use mod_perform\models\activity\helpers\element_usage as base_element_usage;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element as element_model;
use mod_perform\models\activity\section_element_reference;
use performelement_aggregation\data_provider\aggregation_data;
use stdClass;

class aggregation extends derived_responses_element_plugin {

    /**
     * @string The serialized key for a the source section element ids, stored in the perform_section_element_reference table.
     */
    public const SOURCE_SECTION_ELEMENT_IDS = 'sourceSectionElementIds';

    /**
     * @string The serialized key for values to be excluded from calculations, stored in the perform_element.data field as json.
     */
    public const EXCLUDED_VALUES = 'excludedValues';

    /**
     * @string The serialized key for calculations to be run, stored in the perform_element.data field as json.
     */
    public const CALCULATIONS = 'calculations';


    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 120;
    }

    /**
     * @inheritDoc
     */
    public function validate_element(element_entity $element): void {
        $data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        if (!array_key_exists(self::EXCLUDED_VALUES, $data) || !is_array($data[self::EXCLUDED_VALUES])) {
            throw new coding_exception(self::EXCLUDED_VALUES . ' must be an array specified in the element data field');
        }

        foreach ($data[self::EXCLUDED_VALUES] as $excluded_value) {
            if (!is_numeric($excluded_value) && trim($excluded_value ?? '') !== '') {
                throw new coding_exception(self::EXCLUDED_VALUES . ' must be numeric.');
            }
        }

        if (!array_key_exists(self::CALCULATIONS, $data) || !is_array($data[self::CALCULATIONS])) {
            throw new coding_exception(self::CALCULATIONS . ' must be an array specified in the element data field');
        }

        if (count($data[self::CALCULATIONS]) < 1) {
            throw new coding_exception(self::CALCULATIONS . ' must have at least one value');
        }

        foreach ($data[self::CALCULATIONS] as $calculation_method) {
            if (!calculation_method::is_valid_method_name($calculation_method)) {
                throw new coding_exception(self::EXCLUDED_VALUES . ' must be valid calculation plugin names');
            }
        }

        if (!array_key_exists(self::SOURCE_SECTION_ELEMENT_IDS, $data) ||
            $data[self::SOURCE_SECTION_ELEMENT_IDS] === null ||
            count($data[self::SOURCE_SECTION_ELEMENT_IDS]) === 0
        ) {
            throw new coding_exception(self::SOURCE_SECTION_ELEMENT_IDS . ' must be an array specified in the element data field');
        }

        $source_section_element_ids = array_unique($data[self::SOURCE_SECTION_ELEMENT_IDS]);
        $source_section_elements = section_element_reference::get_source_section_elements($source_section_element_ids);

        if (count($source_section_element_ids) !== count($source_section_elements)) {
            throw new coding_exception('Not all supplied source section elements exist');
        }

        foreach ($source_section_elements as $source_section_element) {
            if (!$source_section_element->get_element()->get_element_plugin()->get_is_aggregatable()) {
                throw new coding_exception(
                    "The supplied source section elements are not all aggregatable: \"{$source_section_element->get_element()->title}\" is not aggregatable"
                );
            }
        }

        foreach ($source_section_elements as $source_section_element) {
            $this->ensure_source_section_element_is_in_referencing_activity($element, $source_section_element);
        }
    }

    /**
     * @inheritDoc
     */
    public function get_group(): int {
        return self::GROUP_OTHER;
    }

    /**
     * @inheritDoc
     */
    public function process_data(element_entity $element): ?string {
        $modified_data = (new aggregation_data())->include_extra_info($element);

        return json_encode($modified_data, JSON_THROW_ON_ERROR);
    }


    /**
     * @inheritDoc
     */
    public function get_extra_config_data(): array {
        return [
            self::CALCULATIONS => array_map(function (calculation_method $calculation_method) {
                return [
                    'name' => $calculation_method->get_name(),
                    'label' => $calculation_method->get_label(),
                ];
            }, calculation_method::get_aggregation_calculation_methods()),
        ];
    }

    /**
     * @inheritDoc
     */
    public function post_create(element_model $element): void {
        $data = json_decode($element->get_raw_data(), true, 512, JSON_THROW_ON_ERROR);
        $source_section_element_ids = $data[self::SOURCE_SECTION_ELEMENT_IDS];

        foreach ($source_section_element_ids as $source_section_element_id) {
            section_element_reference::create($source_section_element_id, $element->id);
        }

        $this->strip_section_element_references($element, $data);
    }

    /**
     * @inheritDoc
     */
    public function post_update(element_model $element): void {
        $data = json_decode($element->get_raw_data(), true, 512, JSON_THROW_ON_ERROR);
        $source_section_element_id = $data[self::SOURCE_SECTION_ELEMENT_IDS];

        section_element_reference::patch_multiple($source_section_element_id, $element->id);

        $this->strip_section_element_references($element, $data);
    }

    protected function ensure_source_section_element_is_in_referencing_activity(element_entity $element, section_element $source_section_element): void {
        $source_activity_id = $source_section_element->section->activity_id;

        // This breaks down if/when we implement question banks (re-usable abstract element configurations).
        /** @var section_element[] $referencing_section_elements */
        $referencing_section_elements = section_element_entity::repository()
            ->where('element_id', $element->id)
            ->with('section')
            ->get();

        foreach ($referencing_section_elements as $referencing_section_element) {
            $referencing_activity_id = $referencing_section_element->section->activity_id;

            if ((int) $referencing_activity_id !== (int) $source_activity_id) {
                throw new coding_exception(
                    'Source section elements must be from the same activity as a referencing aggregation element'
                );
            }
        }
    }

    public function format_response_lines(?string $encoded_response_data, ?string $encoded_element_data): array {
        $data = $this->decode_response($encoded_response_data, $encoded_element_data);
        if (!$data) {
            return [];
        }
        return self::get_formatted_response_lines($data);
    }

    /**
     * @param array $decoded_response_data
     * @return array
     */
    public static function get_formatted_response_lines(array $decoded_response_data): array {
        $formatted = [];

        foreach ($decoded_response_data as $calculation => $value) {
            if (!calculation_method::is_valid_method_name($calculation)) {
                continue;
            }

            $a = new stdClass();
            $a->label = calculation_method::load_by_method($calculation)->get_label();
            $a->value = format_float($value, 2);

            $formatted[] = get_string('aggregated_response_display', 'performelement_aggregation', $a);
        }

        return $formatted;
    }

    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data): ?array {
        if ($encoded_response_data === null) {
            return null;
        }

        return json_decode($encoded_response_data, true, 512, JSON_THROW_ON_ERROR);
    }

    private function strip_section_element_references(element_model $element, array $data): void {
        // Strip this from the data, otherwise it can become incorrect if an element/activity is cloned.
        // We can safely do this because all data for this type of element is saved in the section_element_reference table.
        unset($data[self::SOURCE_SECTION_ELEMENT_IDS]);
        $element->update_data(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @inheritDoc
     */
    public function get_element_usage(): base_element_usage {
        return new element_usage();
    }
}
