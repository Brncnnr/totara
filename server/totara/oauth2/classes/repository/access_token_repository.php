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
namespace totara_oauth2\repository;

use core\orm\entity\repository;
use totara_oauth2\entity\access_token;

/**
 * Repository layer for table "ttr_oauth2_access_token"
 * @method access_token|null one(bool $strict = false)
 */
class access_token_repository extends repository {
    /**
     * Returns null when access token is not found, otherwise an entity of access_token.
     * Pass $strict to make sure that we throw exception when record is not found.
     *
     * @param string $token_identifier
     * @param bool $strict
     * @return access_token|null
     */
    public function find_by_identifier(string $token_identifier, bool $strict = false): ?access_token {
        $repository = access_token::repository();
        $repository->where("identifier", $token_identifier);

        $entity = $repository->one($strict);

        if (is_null($entity)) {
            return null;
        }

        if (!is_null($entity->client_provider) && !$entity->client_provider->status) {
            return null;
        }

        return $entity;
    }
}