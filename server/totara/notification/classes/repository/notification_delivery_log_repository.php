<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\repository;

use core\orm\entity\repository;

class notification_delivery_log_repository extends repository {
    /**
     * @param int $notification_log_id
     * @return $this
     */
    public function find_by_notification_log_id(int $notification_log_id): notification_delivery_log_repository {
        $this->where('notification_log_id', $notification_log_id);

        return $this;
    }
}