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

use core\orm\entity\repository;
use totara_evidence\models;

class evidence_item_repository extends repository {

    /**
     * Return only items that have a standard type
     *
     * @return $this
     */
    public function filter_by_standard_location(): self {
        if (!$this->builder->has_join(evidence_type::TABLE, 'type')) {
            $this->join([evidence_type::TABLE, 'type'], 'typeid', 'id');
        }
        $this->where('type.location', models\evidence_type::LOCATION_EVIDENCE_BANK);

        return $this;
    }

    /**
     * Return only items that have a system type
     *
     * @return $this
     */
    public function filter_by_rol_location(): self {
        if (!$this->builder->has_join(evidence_type::TABLE, 'type')) {
            $this->join([evidence_type::TABLE, 'type'], 'typeid', 'id');
        }
        $this->where('type.location', models\evidence_type::LOCATION_RECORD_OF_LEARNING);

        return $this;
    }

}
