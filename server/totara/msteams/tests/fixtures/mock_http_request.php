<?php
/**
 * This file is part of Totara Learn
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_msteams
 */

defined('MOODLE_INTERNAL') || die;

class mock_http_request {
    /**
     * @var array
     */
    private static $domain;

    /**
     * @param string $url
     * @return void
     */
    public static function add_mock_domain(string $domain): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("Not a unit test environment", DEBUG_DEVELOPER);
            return;
        }

        self::$domain = $domain;
    }

    /**
     * @param string $domain
     * @return array|null
     */
    public static function get_body(string $domain): ?array {
        if (!isset(static::$domain)) {
            return null;
        }

        if (static::$domain === $domain) {
            return self::get_mock_response();
        }

        return null;
    }

    /**
     * @return void
     */
    public static function clear(): void {
        static::$domain = null;
    }

    /**
     * @return array
     */
    public static function get_mock_response(): array {
        return [
            'token_endpoint' => 'https://login.microsoftonline.com/25f77c55-1f63-491c-93d0-938de2281cfc/oauth2/token',
            'token_endpoint_auth_methods_supported' =>
                [
                    'client_secret_post',
                    'private_key_jwt',
                    'client_secret_basic',
                ],
            'jwks_uri' => 'https://login.microsoftonline.com/common/discovery/keys',
            'response_modes_supported' =>
                [
                    'query',
                    'fragment',
                    'form_post',
                ],
            'subject_types_supported' => ['pairwise'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'response_types_supported' =>
                [
                    'code',
                    'id_token',
                    'code id_token',
                    'token id_token',
                    'token',
                ],
            'scopes_supported' => ['openid'],
            'issuer' => 'https://sts.windows.net/25f77c55-1f63-491c-93d0-938de2281cfc/',
            'microsoft_multi_refresh_token' => true,
            'authorization_endpoint' => 'https://login.microsoftonline.com/25f77c55-1f63-491c-93d0-938de2281cfc/oauth2/authorize',
            'device_authorization_endpoint' => 'https://login.microsoftonline.com/25f77c55-1f63-491c-93d0-938de2281cfc/oauth2/devicecode',
            'http_logout_supported' => true,
            'frontchannel_logout_supported' => true,
            'end_session_endpoint' => 'https://login.microsoftonline.com/25f77c55-1f63-491c-93d0-938de2281cfc/oauth2/logout',
            'check_session_iframe' => 'https://login.microsoftonline.com/25f77c55-1f63-491c-93d0-938de2281cfc/oauth2/checksession',
            'userinfo_endpoint' => 'https://login.microsoftonline.com/25f77c55-1f63-491c-93d0-938de2281cfc/openid/userinfo',
            'tenant_region_scope' => 'OC',
            'cloud_instance_name' => 'microsoftonline.com',
            'cloud_graph_host_name' => 'graph.windows.net',
            'msgraph_host' => 'graph.microsoft.com',
            'rbac_url' => 'https://pas.windows.net',
        ];
    }

    /**
     * @return string
     */
    public static function get_tenant_id(): string {
        return '25f77c55-1f63-491c-93d0-938de2281cfc';
    }
}