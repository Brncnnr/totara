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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\repository;
use mod_perform\models\activity\track_status;
use mod_perform\state\activity\active;

/**
 * Repository for track assignment entities
 */
final class track_assignment_repository extends repository {

    /**
     * @param array $ids
     * @return $this
     */
    public function filter_by_ids(array $ids): self {
        $this->where('id', $ids);

        return $this;
    }

    /**
     * Return only assignments marked for expansion
     *
     * @return $this
     */
    public function filter_by_expand(): self {
        $this->where('expand', true);

        return $this;
    }

    /**
     * Return only assignments which have an active track and an active activity
     *
     * @return $this
     */
    public function filter_by_active_track_and_activity(): self {
        return $this->join([track::TABLE, 't'], 'track_id', 'id')
            ->join([activity::TABLE, 'a'], 't.activity_id', 'id')
            ->where('t.status', track_status::ACTIVE)
            ->where('a.status', active::get_code());
    }

}
