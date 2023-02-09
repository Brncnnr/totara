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

class track_repository extends repository {

    /**
     * Return only tracks marked for schedule synchronisation.
     *
     * @return $this
     */
    public function filter_by_schedule_needs_sync(): self {
        $this->where('schedule_needs_sync', true);

        return $this;
    }

    /**
     * Return only active tracks.
     *
     * @return $this
     */
    public function filter_by_active(): self {
        $this->where('status', track_status::ACTIVE);

        return $this;
    }

    /**
     * Return only tracks of active activities.
     *
     * @return $this
     */
    public function filter_by_active_activity(): self {
        return $this->join([activity::TABLE, 'a'], 'activity_id', 'id')
            ->where('a.status', active::get_code());
    }

}