<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package virtualmeeting_msteams
 */

namespace virtualmeeting_msteams\providers;

use core\entity\user;
use totara_core\entity\virtual_meeting_auth;
use totara_core\http\client;
use totara_core\http\request;
use totara_core\virtualmeeting\authoriser\oauth2_authoriser;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\plugin\provider\auth_provider;
use totara_core\virtualmeeting\user_auth;
use virtualmeeting_msteams\constants;

/**
 * User authentication
 */
class auth implements auth_provider {
    /** @var client */
    private $client;

    /**
     * Constructor.
     *
     * @param client $client
     * @codeCoverageIgnore
     */
    public function __construct(client $client) {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function get_authentication_endpoint(): string {
        return oauth2_authoriser::make_login_url(
            'msteams',
            constants::OAUTH2_AUTH_ENDPOINT,
            get_config('virtualmeeting_msteams', 'client_id'),
            constants::SCOPE_MEETING,
            [
                'prompt' => 'select_account',
                'state' => sesskey(),
            ]);
    }

    /**
     * @inheritDoc
     */
    public function get_profile(user $user, bool $update): array {
        $userauth = user_auth::load('msteams', $user, true);
        $auth = self::create_authoriser($this->client);
        $token = $userauth->get_fresh_token($auth, $update);
        $headers = ['Authorization' => 'Bearer ' . $token];
        $request = request::get(constants::USERINFO_API_ENDPOINT, $headers);
        $response = $this->client->execute($request);
        $response->throw_if_error();
        $json = $response->get_body_as_json(false, true);
        $result = ['name' => $json->displayName ?? $json->mail ?? $json->userPrincipalName];
        if (isset($json->mail) || isset($json->userPrincipalName)) {
            $result['email'] = $json->mail ?? $json->userPrincipalName;
        }
        if (isset($json->displayName)) {
            $result['friendly_name'] = $json->displayName;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function authorise(user $user, string $method, array $headers, string $body, array $query_get, array $query_post): void {
        if (!isset($query_get['state']) || !isset($query_get['code'])) {
            throw auth_exception::invalid_request();
        }
        $state = $query_get['state'];
        if (!confirm_sesskey($state)) {
            throw auth_exception::invalid_request();
        }
        $code = $query_get['code'];
        user_auth::create_or_replace('msteams', $user, function (virtual_meeting_auth $entity) use ($code) {
            $auth = self::create_authoriser($this->client);
            $auth->authorise($entity, $code);
        });
    }

    /**
     * @return array of parameters passed to the OAuth2 token endpoint
     */
    private static function params(): array {
        return [
            'client_id' => get_config('virtualmeeting_msteams', 'client_id'),
            'client_secret' => get_config('virtualmeeting_msteams', 'client_secret'),
        ];
    }

    /**
     * Create an oauth2_authoriser instance.
     *
     * @param client $client
     * @return oauth2_authoriser
     */
    public static function create_authoriser(client $client): oauth2_authoriser {
        return new oauth2_authoriser($client, constants::OAUTH2_TOKEN_ENDPOINT, constants::SCOPE_MEETING, self::params());
    }
}
