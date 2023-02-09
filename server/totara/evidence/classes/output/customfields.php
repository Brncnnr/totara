<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\output;

use core\output\template;
use totara_evidence\customfield_area\evidence;
use totara_evidence\customfield_area\field_helper;
use totara_evidence\entity\evidence_field_data;
use totara_evidence\models\evidence_item;
use totara_evidence\models\helpers\multilang_helper;

class customfields extends template {

    /**
     * Generate label and value for a single evidence custom field
     *
     * @param evidence_field_data $data Evidence item field data
     * @return array Label and field value
     */
    protected static function create_single_field(evidence_field_data $data): array {
        $field = $data->field;

        return [
            'label' => multilang_helper::parse_field_name_string($field->fullname),
            'value' => field_helper::get_field_class($field->datatype)::display_item_data(
                $data->data,
                [
                    'prefix'   => evidence::get_prefix(),
                    'itemid'   => $data->id,
                    'extended' => true
                ]
            ),
        ];
    }

    /**
     * Generate custom field data list for a given evidence item
     *
     * @param evidence_item $item Evidence item to show fields for
     * @return customfields Field HTML
     */
    public static function create(evidence_item $item): self {
        $fields = [];
        foreach ($item->get_customfield_data() as $field_data) {
            /** @var evidence_field_data $field_data */
            if (!$field_data->field->hidden) {
                $fields[] = self::create_single_field($field_data);
            }
        }

        return new static(['fields' => $fields]);
    }

}
