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
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\entity;

use contentmarketplace_linkedin\repository\user_progress_repository;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $learning_object_urn
 * @property int $progress
 * @property int $time_created
 * @property int $time_updated
 * @property int $time_completed
 *
 * @property learning_object|null $learning_object
 *
 * @method static user_progress_repository repository()
 */
class user_progress extends entity {
    /**
     * @var string
     */
    public const TABLE = "marketplace_linkedin_user_progress";

    // We intentionally handle the CREATED_TIMESTAMP / UPDATED_TIMESTAMP manually as LinkedIn's API sends us the timestamps to use.

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return user_progress_repository::class;
    }

    /**
     * @return belongs_to
     */
    public function learning_object(): belongs_to {
        return $this->belongs_to(learning_object::class, 'learning_object_urn', 'urn');
    }

}