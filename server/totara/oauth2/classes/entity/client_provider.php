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
 * @package totara_oauth2
 */
namespace totara_oauth2\entity;

use core\entity\tenant;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many_through;
use totara_api\entity\client;
use totara_api\entity\client_oauth2_client_provider;
use totara_oauth2\repository\client_provider_repository;

/**
 * An entity class that map with a record of table "ttr_totara_oauth2_client_provider".
 *
 * @property int $id
 * @property string $client_id
 * @property string $client_secret
 * @property string $id_number
 * @property string $name
 * @property string|null $description
 * @property int|null $description_format
 * @property string|null $scope
 * @property string|null $grant_types
 * @property int $internal Whether this provider is created programatically and hidden from UI
 * @property int $time_created
 * @property int $status
 * @property int|null $tenant_id
 * @property string|null $component
 *
 * @property-read tenant $tenant
 * @property-read client[] $clients
 *
 * @method static client_provider_repository repository()
 */
class client_provider extends entity {
    /**
     * @var string
     */
    public const TABLE = "totara_oauth2_client_provider";

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = "time_created";

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return client_provider_repository::class;
    }

    /**
     * @return array
     */
    public function get_grant_types_as_array(): array {
        if (empty($this->grant_types)) {
            return [];
        }

        return explode(" ", $this->grant_types);
    }

    /**
     * Get the tenant.
     *
     * @return belongs_to
     */
    public function tenant(): belongs_to {
        return $this->belongs_to(tenant::class, 'tenant_id');
    }

    /**
     * @return has_many_through
     */
    public function clients(): has_many_through {
        return $this->has_many_through(
            client_oauth2_client_provider::class,
            client::class,
            'id',
            'client_provider_id',
            'client_id',
            'id'
        );
    }

}