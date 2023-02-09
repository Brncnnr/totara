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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\entity;

use core\entity\tenant;
use core\entity\user;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many_through;
use core\orm\entity\relations\has_one;
use totara_api\repository\client_repository;
use totara_oauth2\entity\client_provider;


/**
 * An entity class that maps with a record of table "totara_api_client".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $tenant_id
 * @property int $time_created
 * @property int $status
 * @property int|null $user_id
 *
 * @property-read tenant $tenant
 * @property-read collection|client_provider[] $oauth2_client_providers
 * @property-read client_settings $client_settings
 * @property-read client_rate_limit $client_rate_limit
 * @property-read user $user
 *
 * @method static client_repository repository()
 */
class client extends entity {
    /**
     * @var string
     */
    public const TABLE = "totara_api_client";

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = "time_created";

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return client_repository::class;
    }

    /**
     * The tenant for this client.
     *
     * @return belongs_to
     */
    public function tenant(): belongs_to {
        return $this->belongs_to(tenant::class, 'tenant_id');
    }

    /**
     * OAuth2 client providers for this client
     *
     * @return has_many_through
     */
    public function oauth2_client_providers(): has_many_through {
        return $this->has_many_through(
            client_oauth2_client_provider::class,
            client_provider::class,
            'id',
            'client_id',
            'client_provider_id',
            'id'
        )->where('internal', '=', 1);
    }

    /**
     * @return has_one
     */
    public function client_settings(): has_one {
        return $this->has_one(client_settings::class, 'client_id');
    }

    /**
     * Get the client rate limit.
     * @return has_one
     */
    public function client_rate_limit(): has_one {
        return $this->has_one(client_rate_limit::class, 'client_id');
    }

    /**
     * Get the API user for the client.
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }

}