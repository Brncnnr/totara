<?php
/**
 * This file is part of Totara Core
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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_api\repository\client_settings_repository;

/**
 * An entity class that maps with a record of table "ttr_totara_api_client_settings".
 *
 * @property int $id
 * @property int $default_token_expiry_time
 * @property int $client_rate_limit
 * @property int|null $response_debug
 * @property int $client_id
 * @property int $time_created
 *
 * @property-read client $client
 *
 * @method static client_settings_repository repository()
 */
class client_settings extends entity {
    /**
     * @var string
     */
    public const TABLE = "totara_api_client_settings";

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = "time_created";

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return client_settings_repository::class;
    }

    /**
     * @return belongs_to
     */
    public function client(): belongs_to {
        return $this->belongs_to(client::class, 'client_id');
    }

}