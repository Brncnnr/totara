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
use totara_evidence\models\evidence_type;

class evidence_type_repository extends repository {

    /**
     * Return only active types
     *
     * @return $this
     */
    public function filter_by_active(): self {
        $this->where('status', evidence_type::STATUS_ACTIVE);

        return $this;
    }

    /**
     * Return only hidden types
     *
     * @return $this
     */
    public function filter_by_hidden(): self {
        $this->where('status', evidence_type::STATUS_HIDDEN);

        return $this;
    }

    /**
     * Return only standard types
     *
     * @return $this
     */
    public function filter_by_standard_location(): self {
        $this->where_not_in('idnumber', evidence_type::DEFAULT_SYSTEM_TYPES);

        return $this;
    }

    /**
     * Return only system types
     *
     * @return $this
     */
    public function filter_by_system_location(): self {
        $this->where_in('idnumber', evidence_type::DEFAULT_SYSTEM_TYPES);

        return $this;
    }

}
