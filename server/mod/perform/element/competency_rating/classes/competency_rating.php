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

namespace performelement_competency_rating;

use coding_exception;
use context_system;
use core\collection;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\element_usage as base_element_usage;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\response\element_validation_error;
use totara_hierarchy\entity\scale_value;

class competency_rating extends respondable_element_plugin {

    /**
     * @inheritDoc
     */
    public function get_title_help_text(): ?string {
        return get_string('help_text', 'performelement_competency_rating');
    }

    /**
     * Validation error code for a required competency rating.
     */
    private const VALIDATION_ERROR_CODE = 'COMPETENCY_REQUIRED';

    /**
     * @inheritDoc
     */
    public function validate_response(?string $encoded_response_data, ?element $element, $is_draft_validation = false): collection {
        $selected_scale_value_id = json_decode($encoded_response_data);
        $scale_value = null;

        if ($selected_scale_value_id) {
            $scale_value = $this->get_scale_value($selected_scale_value_id);

            if (is_null($scale_value)) {
                throw new coding_exception('Scale value does not exist.');
            }
        }
        $errors = new collection();

        if ($this->fails_required_validation(is_null($scale_value), $element, $is_draft_validation)) {
            $error_message = get_string('error_answer_required', 'performelement_competency_rating');
            $validation_error = new element_validation_error(self::VALIDATION_ERROR_CODE, $error_message);
            $errors->append($validation_error);
        }

        return $errors;
    }

    /**
     * @inheritDoc
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data) {
        $selected_scale_value_id = json_decode($encoded_response_data);

        if (empty($selected_scale_value_id)) {
            return null;
        }
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());
        $scale_value = $this->get_scale_value($selected_scale_value_id);

        return $formatter->format($scale_value->name) ?? get_string('scale_value_deleted', 'performelement_competency_rating');
    }

    /**
     * Get scale value.
     *
     * @param int $scale_value_id
     * @return scale_value|null
     */
    private function get_scale_value(int $scale_value_id): ?scale_value {
        return scale_value::repository()->find($scale_value_id);
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 70;
    }

    /**
     * @inheritDoc
     */
    public function get_element_usage(): base_element_usage {
        return new element_usage();
    }

}