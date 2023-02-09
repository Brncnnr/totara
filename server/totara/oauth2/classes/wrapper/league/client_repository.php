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
namespace totara_oauth2\wrapper\league;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use totara_oauth2\entity\client_provider;
use totara_oauth2\grant_type;

class client_repository implements ClientRepositoryInterface {
// phpcs:disable Totara.NamingConventions
    /**
     * @param string $clientIdentifier
     * @return client_entity|null
     */
    public function getClientEntity($clientIdentifier): ?client_entity {
        $repository = client_provider::repository();
        $entity = $repository->find_by_client_id($clientIdentifier);

        if (null === $entity) {
            return null;
        }

        return new client_entity($entity);
    }

    /**
     * @param string      $clientIdentifier
     * @param string|null $clientSecret
     * @param string|null $grantType
     *
     * @return bool
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool {
        if (grant_type::get_client_credentials() !== $grantType) {
            // Invalid grant type.
            return false;
        }

        $client_entity = $this->getClientEntity($clientIdentifier);
        return $client_entity->verify($clientSecret);
    }
// phpcs:enable
}