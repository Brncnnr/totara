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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\entity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Virtual meeting auth token entity
 *
 * @property-read int $id ID
 * @property-read string $plugin
 * @property string $access_token
 * @property string $refresh_token
 * @property int $timeexpiry
 * @property-read int $timecreated
 * @property-read int $timemodified
 * @property-read int $userid
 * @property-read user $user
 * @method static virtual_meeting_auth_repository repository()
 */
final class virtual_meeting_auth extends entity {

    public const TABLE = 'virtualmeeting_auth';

    public const CREATED_TIMESTAMP = 'timecreated';

    public const UPDATED_TIMESTAMP = 'timemodified';

    public const CLOCK_SKEW = 5;

    /**
     * Check whether the token is expired or not
     *
     * @param integer $time timestamp or 0 for the current time
     * @return boolean
     */
    public function is_expired(int $time = 0): bool {
        if (!$time) {
            $time = time();
        }
        return $this->timeexpiry <= $time + self::CLOCK_SKEW;
    }

    /**
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'userid');
    }
}
