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

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use totara_oauth2\wrapper\league\scope\xapi_write;

/**
 * The class is not used much for now.
 */
class scope_repository implements ScopeRepositoryInterface {
// phpcs:disable Totara.NamingConventions
    /**
     * @param string $identifier
     * @return ScopeEntityInterface|null
     */
    public function getScopeEntityByIdentifier($identifier) {
        if (xapi_write::IDENTIFIER === $identifier) {
            return new xapi_write();
        }

        return null;
    }

    /**
     * @param array                 $scopes
     * @param string                $grantType
     * @param ClientEntityInterface $clientEntity
     * @param null                  $userIdentifier
     *
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        return $scopes;
    }
// phpcs:enable
}