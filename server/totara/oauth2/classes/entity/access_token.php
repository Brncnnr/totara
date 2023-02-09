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

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_oauth2\repository\access_token_repository;

/**
 * An entity class that maps with the record from table "ttr_totara_oauth2_access_token".
 *
 * @property int $id
 * @property string $client_id
 * @property int $client_provider_id
 * @property string $identifier
 * @property int $expires
 * @property string|null $scope
 *
 * @property-read client_provider $client_provider
 * @method static access_token_repository repository()
 */
class access_token extends entity {
    /**
     * @var string
     */
    public const TABLE = "totara_oauth2_access_token";

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return access_token_repository::class;
    }

    /**
     * @return belongs_to
     */
    public function client_provider(): belongs_to {
        return $this->belongs_to(client_provider::class, "client_provider_id");
    }
}