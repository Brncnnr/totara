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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */

namespace totara_oauth2\wrapper\league;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use DateInterval;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use totara_api\entity\client;
use totara_api\global_api_config;
use totara_oauth2\client\client_factory;

/**
 * A wrapper for the PHPLeaque OAuth2 ClientCredentialsGrant class, so we can adjust the access token expiry time.
 * Unit tests for this are in totara_oauth2_server_testcase.
 */
class client_credentials_grant extends ClientCredentialsGrant {
    /**
     * @inheritDoc
     */
    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        $userIdentifier,
        array $scopes = []
    ) {
        if ($client instanceof client_entity) {
            // We have a client provider entity here, lookup the client and overwrite $accessTokenTTL
            $client_provider_entity = $client->get_client_provider();
            $component = $client_provider_entity->component;
            if (!empty($client_provider_entity->internal) && !empty($component)) {
                $oauth2_client = client_factory::create_client($component);
                if (!$oauth2_client->can_create_token($client_provider_entity)) {
                    throw OAuthServerException::accessDenied("'{$oauth2_client->get_component()}' feature is not enabled.");
                }

                /** @var client $client_entity */
                $client_entity = $client_provider_entity->clients->first();

                $seconds = 0;
                if ($client_entity && $client_entity->client_settings) {
                    $seconds = $client_entity->client_settings->default_token_expiry_time ?? 0;
                }

                if (empty($seconds)) {
                    $seconds = global_api_config::get_default_token_expiration();
                }

                $seconds = (int) $seconds;
                if ($seconds > 0) {
                    $accessTokenTTL = new DateInterval('PT' . $seconds . 'S');
                }
            }
        }

        return parent::issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);
    }
}