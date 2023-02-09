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
 * Virtual meeting entity
 *
 * @property-read int $id ID
 * @property string $plugin
 * @property int $userid
 * @property-read int $timecreated
 * @property-read int $timemodified
 * @property-read user $user
 */
final class virtual_meeting extends entity {

    public const TABLE = 'virtualmeeting';

    public const CREATED_TIMESTAMP = 'timecreated';

    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'userid');
    }

}
