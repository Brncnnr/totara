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

namespace totara_evidence\entity;

use core\orm\collection;
use core\orm\entity\entity;

/**
 * Evidence item field data entity
 *
 * @property-read int $id ID
 * @property int $fieldid Custom field ID
 * @property int $evidenceid Evidence item ID
 * @property string $data Field data
 * @property-read evidence_item $item Evidence item
 * @property-read evidence_type_field $field Custom field
 * @property-read collection<evidence_field_data_param> $params Additional field data params
 *
 * @package totara_evidence\entity
 */
class evidence_field_data extends entity {

    /**
     * @var string
     */
    public const TABLE = 'totara_evidence_type_info_data';

    /**
     * Get the evidence item that this custom field data belongs to
     *
     * @return evidence_item
     */
    protected function get_item_attribute(): evidence_item {
        return new evidence_item($this->evidenceid);
    }

    /**
     * Get the custom field that this data is an instance of
     *
     * @return evidence_type_field
     */
    protected function get_field_attribute(): evidence_type_field {
        return new evidence_type_field($this->fieldid);
    }

    /**
     * The additional data parameters for this field data
     *
     * @return collection
     */
    protected function get_params_attribute(): collection {
        if ($this->id) {
            return evidence_field_data_param::repository()
                ->where('dataid', $this->id)
                ->order_by('id')
                ->get();
        } else {
            return new collection([]);
        }
    }

}
