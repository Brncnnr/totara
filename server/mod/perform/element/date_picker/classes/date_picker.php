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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package performelement_date_picker
 */

namespace performelement_date_picker;

use coding_exception;
use core\collection;
use DateTime;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\respondable_element_plugin;

class date_picker extends respondable_element_plugin {

    private const DEFAULT_YEAR_RANGE_OFFSET = 50;
    private const DEFAULT_MIN_YEAR = 1000;

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 80;
    }

    /**
     * @inheritDoc
     */
    public function validate_element(element_entity $element): void {
        if (!$element->data) {
            return;
        }

        $decoded_data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        $this->validate_years_config(
            $decoded_data['yearRangeStart'] ?? null,
            $decoded_data['yearRangeEnd'] ?? null,
        );
    }

    /**
     * Validate years configuration so that it stays within our min/max range
     *
     * @param int|null $year_range_start
     * @param int|null $year_range_end
     */
    private function validate_years_config(
        ?int $year_range_start,
        ?int $year_range_end
    ): void {
        if (empty($year_range_start) || empty($year_range_end)) {
            throw new coding_exception('Year range cannot be empty');
        }

        $min_year = $this->get_default_min_year();
        if ($year_range_start < $min_year) {
            throw new coding_exception("Year range start must be {$min_year} or more");
        }

        $max_year = $this->get_default_max_year();
        if ($year_range_end > ($max_year)) {
            throw new coding_exception("Year range end must be {$max_year} or less");
        }

        if ($year_range_start !== null && $year_range_end !== null && $year_range_start > $year_range_end) {
            throw new coding_exception('Year range start must less than or equal to year range end');
        }
    }

    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        $response_data = json_decode($encoded_response_data, true, 512, JSON_THROW_ON_ERROR);

        $errors = new collection();

        if ($this->fails_required_validation(is_null($response_data), $element, $is_draft_validation)) {
            $errors->append(new answer_required_error());
        }

        if (!is_null($response_data)) {
            if (!isset($response_data['iso'])) {
                $errors->append(new date_iso_required_error());
            } else {
                $date_object = DateTime::createFromFormat('Y-m-d', $response_data['iso']);

                if ($date_object === false) {
                    $errors->append(new invalid_date_error());
                }

                $year_outside_range = $date_object ? $this->validate_selected_year($element, $date_object) : null;
                if ($year_outside_range !== null) {
                    $errors->append($year_outside_range);
                }
            }
        }

        return $errors;
    }

    /**
     * Pull the answer text string out of the encoded json data.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|string[]
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data) {
        return userdate(
            $this->get_response_timestamp($encoded_response_data),
            get_string('strftimedatefullshort', 'langconfig')
        );
    }

    /**
     * @inheritDoc
     */
    public function format_response_lines(?string $encoded_response_data, ?string $encoded_element_data): array {
        $decoded_response = $this->get_response_timestamp($encoded_response_data);

        if ($decoded_response === null) {
            return [];
        }

        $formatted_date = userdate(
            $decoded_response,
            get_string('strftimedate', 'langconfig')
        );

        return [$formatted_date];
    }

    /**
     * Pull the timestamp of the response date out of the encoded response.
     *
     * @param string|null $encoded_response_data
     * @return int|null
     */
    private function get_response_timestamp(?string $encoded_response_data): ?int {
        $response_data = is_null($encoded_response_data) ? null : json_decode($encoded_response_data, true);

        if ($response_data === null) {
            return null;
        }

        $date_object = DateTime::createFromFormat('Y-m-d', $response_data['iso']);

        return $date_object->getTimestamp();
    }

    /**
     * Check the year is inside the allowed range.
     *
     * @param element|null $element
     * @param DateTime $selected_date
     * @return year_outside_range|null Return the error object if the year is outside the range, otherwise null
     */
    public function validate_selected_year(?element $element, DateTime $selected_date): ?year_outside_range {
        $min_year = $this->get_default_min_year();
        $max_year = $this->get_default_max_year();

        if ($element !== null) {
            $decoded_data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);
            $min_year = $decoded_data['yearRangeStart'] ?? $min_year;
            $max_year = $decoded_data['yearRangeEnd'] ?? $max_year;
        }

        $selected_year = (int) $selected_date->format('Y');
        if ($selected_year < $min_year || $selected_year > $max_year) {
            return new year_outside_range($min_year, $max_year);
        }

        return null;
    }

    public function get_default_min_year(): int {
        return self::DEFAULT_MIN_YEAR;
    }

    public function get_default_max_year(): int {
        $current_year = (int) (new DateTime())->format('Y');

        return $current_year + self::DEFAULT_YEAR_RANGE_OFFSET;
    }
}
