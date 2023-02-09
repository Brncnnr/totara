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

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use DateTimeImmutable;
use coding_exception;
use totara_oauth2\entity\access_token;
use totara_oauth2\entity\client_provider;

class token_repository implements AccessTokenRepositoryInterface {
// phpcs:disable Totara.NamingConventions
    /**
     * The expiry times in seconds. By default, it is 24h
     * @var int
     */
    private $expiry;

    /**
     * The current time now.
     * @var int
     */
    private $time_now;

    /**
     * @param int|null $expiry
     * @param int|null $time_now
     */
    public function __construct(?int $time_now = null, ?int $expiry = null) {
        $this->time_now = $time_now ?? time();
        $this->expiry = $expiry ?? DAYSECS;
    }

    /**
     * Creating a new token.
     *
     * @param ClientEntityInterface $clientEntity
     * @param array                 $scopes
     * @param null                  $userIdentifier
     * @return token_entity
     */
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        $userIdentifier = null
    ): token_entity {
        $token = new token_entity($clientEntity);

        // Will expire from the time now plus the expiry times.
        $dt = (new DateTimeImmutable())->setTimestamp($this->time_now + $this->expiry);
        $token->setExpiryDateTime($dt);

        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }

        return $token;
    }

    /**
     * @param AccessTokenEntityInterface $accessTokenEntity
     * @return void
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void {
        $identifier = $accessTokenEntity->getIdentifier();
        if (empty($identifier)) {
            throw new coding_exception("Access token identifier is empty");
        }

        $repository = access_token::repository();
        $access_token = $repository->find_by_identifier($identifier);

        if (null !== $access_token) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $entity = new access_token();
        $entity->identifier = $identifier;
        $entity->expires = $accessTokenEntity->getExpiryDateTime()->getTimestamp();

        $client_entity = $accessTokenEntity->getClient();
        if ($client_entity instanceof client_entity) {
            $entity->client_provider_id = $client_entity->get_client_provider()->id;
        } else {
            // Fetch from database, this should be less likely a case. But who would know that we are going to
            // reuse this wrapper else where.
            $client_provider = client_provider::repository()->find_by_client_id($client_entity->getIdentifier(), true);
            $entity->client_provider_id = $client_provider->id;
        }

        $scopes = array_map(
            function (ScopeEntityInterface $entity): string {
                return $entity->getIdentifier();
            },
            $accessTokenEntity->getScopes()
        );

        if (!empty($scopes)) {
            $entity->scope = implode(" ", $scopes);
        }

        $entity->save();
    }

    /**
     * @param string $tokenId
     * @return void
     */
    public function revokeAccessToken($tokenId): void {
        // We don't revoke access token for now.
    }

    /**
     * @param string $tokenId
     * @return bool
     */
    public function isAccessTokenRevoked($tokenId): bool {
        // We don't support the revoke of token for now, therefore we are checking against the existing of its.
        $entity = access_token::repository()->find_by_identifier($tokenId);
        return null === $entity;
    }
// phpcs:enable
}