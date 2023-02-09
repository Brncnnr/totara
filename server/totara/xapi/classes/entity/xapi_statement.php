<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_xapi
 */
namespace totara_xapi\entity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * @property int $id
 * @property int $time_created
 * @property string $statement
 * @property int|null $user_id
 * @property string|null $client_id
 *
 * @property-read user|null $user
 */
class xapi_statement extends entity {
    /**
     * @var string
     */
    public const TABLE = "totara_xapi_statement";

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = "time_created";

    /**
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }

}