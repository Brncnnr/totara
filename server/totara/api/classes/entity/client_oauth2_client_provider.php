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

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_oauth2\entity\client_provider;

/**
 * An entity class that maps with a record of table "ttr_totara_api_client_oauth2_client_provider".
 * This is an intermediate table for api_clients and oauth2_client_providers tables
 *
 * @property int $id
 * @property int $time_created
 * @property int $client_id
 * @property int $client_provider_id
 *
 * @property-read client $client
 * @property-read client_provider $client_provider
 */
class client_oauth2_client_provider extends entity {
    /**
     * @var string
     */
    public const TABLE = 'totara_api_client_oauth2_client_provider';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';

    /**
     * The client for this link
     *
     * @return belongs_to
     */
    public function client(): belongs_to {
        return $this->belongs_to(client::class, 'client_id');
    }

    /**
     * The client provider for this link
     *
     * @return belongs_to
     */
    public function client_provider(): belongs_to {
        return $this->belongs_to(client_provider::class, 'client_provider_id');
    }
}