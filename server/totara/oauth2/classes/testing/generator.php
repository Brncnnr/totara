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
namespace totara_oauth2\testing;

use core\testing\component_generator;
use League\OAuth2\Server\CryptKey;
use totara_oauth2\entity\client_provider;
use totara_oauth2\grant_type;
use totara_oauth2\local\crypto_factory;
use totara_oauth2\wrapper\league\client_entity;
use totara_oauth2\wrapper\league\token_entity;
use totara_oauth2\wrapper\league\token_repository;
use DateTimeImmutable;
use coding_exception;

class generator extends component_generator {
    /**
     * This function will preset all the configuration required for oauth2 server to work.
     * Note that without calling this function before your testing, the system will auto generate
     * the required configuration, which it would slow the test down. Hence, make sure you that you should
     * call to this function before executing any tests related to oauth2 server.
     *
     * @return void
     */
    public static function setup_required_configuration(): void {
        global $CFG;

        set_config("private_key_path", "{$CFG->dirroot}/totara/oauth2/tests/fixtures/mock_p_key.pem", "totara_oauth2");
        set_config("encryption_key", base64_encode(random_bytes(32)), "totara_oauth2");
        set_config("public_key_path", "{$CFG->dirroot}/totara/oauth2/tests/fixtures/mock_pu_key.pem", "totara_oauth2");
    }

    /**
     * The array $parameters should have these following attributes:
     * + access_token: String
     * + expires: String
     * + scope: String|array|null - note that if it is a string, it should be a string of
     *                              concatenated list by space.
     *
     * @param string|null $client_id
     * @param array $parameters
     *
     * @return token_entity
     */
    public function create_access_token(string $client_id = null, array $parameters = []): token_entity {
        if (array_key_exists("client_id", $parameters)) {
            debugging(
                "Please do not set the client_id from the parameters, as it will not be used",
                DEBUG_DEVELOPER
            );
        }

        $private_key = crypto_factory::get_private_key_file_path();

        if (empty($client_id)) {
            $client_id = uniqid("client_");
        }

        if (!empty($parameters["scope"])) {
            // Populate the scope of token.
            $scope = $parameters["scope"];
            if (is_string($scope)) {
                $scope = explode(" ", $scope);
                $scope = implode(" ", $scope);
            }

            $parameters["scope"] = $scope;
        }

        $client_provider = client_provider::repository()->find_by_client_id($client_id);
        if (null === $client_provider) {
            $client_provider = $this->create_client_provider($client_id, ["scope" => $parameters["scope"] ?? []]);
        }

        $token_repository = new token_repository();
        $new_token = $token_repository->getNewToken(
            new client_entity($client_provider),
            $parameters["scope"] ?? []
        );

        $identifier = $parameters["access_token"] ?? bin2hex(random_bytes(32));
        $new_token->setIdentifier($identifier);
        $new_token->setPrivateKey(new CryptKey($private_key, null, false));

        // Override the expiry date time from the token repository.
        if (!empty($parameters["expires"])) {
            $new_token->setExpiryDateTime(
                (new DateTimeImmutable())->setTimestamp($parameters["expires"])
            );
        }

        $token_repository->persistNewAccessToken($new_token);
        return $new_token;
    }

    /**
     * @param client_provider $client_provider
     * @param int|null $expires
     * @return token_entity
     */
    public function create_access_token_from_client_provider(
        client_provider $client_provider,
        ?int $expires = null
    ): token_entity {
        $scope = $client_provider->scope;

        if (empty($scope)) {
            // Treating empty string as null.
            $scope = null;
        }

        return $this->create_access_token(
            $client_provider->client_id,
            [
                "scope" => $scope,
                "expires" => $expires
            ]
        );
    }

    /**
     * The array $parameters should have these following attributes:
     * + client_id: string
     * + client_secret: string
     * + name: string
     * + description: string|null
     * + description_format: int|null
     * + scope: string|array|null
     * + grant_types: string|array|null
     * + id_number: string
     *
     * @param string|null $client_id
     * @param array       $parameters
     *
     * @return client_provider
     */
    public function create_client_provider(?string $client_id = null, array $parameters = []): client_provider {
        if (empty($client_id)) {
            $client_id = uniqid("client_");
        }

        // Default to client_credentials for grant type.
        if (!array_key_exists("grant_types", $parameters)) {
            $parameters["grant_types"] = grant_type::get_client_credentials();
        }

        $entity = new client_provider();
        $entity->name = $parameters["name"] ?? 'client provider' . rand(0, 100);
        $entity->client_id = $client_id;
        $entity->client_secret = $parameters["client_secret"] ?? uniqid("secret_");
        $entity->description = $parameters["description"] ?? null;
        $entity->description_format = $parameters["description_format"] ?? null;
        $entity->internal = $parameters['internal'] ?? 0;

        if (!empty($parameters["scope"])) {
            $scope = $parameters["scope"];
            if (is_array($scope)) {
                $scope = implode(" ", $scope);
            }

            $entity->scope = $scope;
        }

        // Process on the grant types for the client provider.
        $grant_types = $parameters["grant_types"];
        if (is_array($grant_types)) {
            $grant_types = implode(" ", $grant_types);
        }

        $entity->grant_types = $grant_types;
        $entity->save();
        $entity->refresh();

        return $entity;
    }

    /**
     * Callback from behat data generator.
     *
     * @param array $parameters
     * @return client_provider
     */
    public function create_client_provider_instance(array $parameters = []): client_provider {
        if (!isset($parameters['name'])) {
            throw new coding_exception(
                "Cannot create client_provider from parameters that does not have the name itself"
            );
        }

        if (empty($parameters['client_id'])) {
            $parameters['client_id'] = uniqid("client_");
        }

        return $this->create_client_provider($parameters['client_id'], $parameters);
    }
}