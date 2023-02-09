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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace totara_evidence\formatter;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use totara_evidence\customfield_area\evidence;
use totara_evidence\customfield_area\field_helper;
use totara_evidence\models\evidence_item as evidence_item_model;

class evidence_item extends entity_model_formatter {

    /** @var evidence_item_model */
    protected $object;

    /**
     * @inheritDoc
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'type' => null,
            'created_at' => date_field_formatter::class,
            'fields' => null,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function has_field(string $field): bool {
        if ($field === 'fields') {
            return true;
        }
        return parent::has_field($field);
    }

    /**
     * @inheritDoc
     */
    protected function get_field(string $field) {
        if ($field === 'fields') {
            $fields = [];
            foreach ($this->object->get_customfield_data() as $field_data) {
                $field = $field_data->field;

                // Create a customfield instance.
                $field_class = field_helper::get_field_class($field->datatype);
                $field_instance = new $field_class(
                    $field->id,
                    $this->object,
                    evidence::get_prefix(),
                    'totara_evidence_type'
                );

                $fields[] = $field_instance->get_raw_field_data(
                    $field_data->data,
                    [
                        'prefix'   => evidence::get_prefix(),
                        'itemid'   => $field_data->id,
                        'extended' => true
                    ]
                );
            }
            return $fields;
        }
        return parent::get_field($field);
    }

}
