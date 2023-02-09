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

namespace totara_msteams;

use stdClass;
use totara_core\http\formdata;
use totara_core\http\response;
use coding_exception;

/**
 * Class msteams_cloud_helper
 * @package totara_msteams
 */
final class msteams_gateway_helper {
    /**
     * msteams_cloud_helper constructor.
     */
    private function __construct() {
    }

    /**
     *
     * @param string $data
     * @return bool
     */
    public static function is_internal_host_allowed(string $data): bool {
        global $CFG;

        $blocked = empty($CFG->link_parser_blocked_hosts) ? [] : explode(',', $CFG->link_parser_blocked_hosts);
        if (in_array($data, $blocked, true)) {
            return false;
        }

        return true;
    }

    /**
     * @param array|stdClass $payload
     * @return string|null
     */
    public static function get_tenant_id($payload): ?string {
        $endpoint = 'https://login.microsoftonline.com/';
        $keys = [
            'token_endpoint',
            'device_authorization_endpoint',
            'authorization_endpoint'
        ];

        $payload = (array) $payload;
        foreach ($keys as $key) {
            if (isset($payload[$key])) {
                $id = substr($payload[$key], 34, 36);
                $host = substr($payload[$key], 0, 34);

                if (!empty($tenant_id) && $tenant_id !== $id && $endpoint !== $host) {
                    return null;
                }

                $tenant_id = $id;
            }
        }

        if (!is_null($tenant_id)) {
            $cleaned = clean_param($tenant_id, PARAM_ALPHANUMEXT);
            return $cleaned;
        }

        return null;
    }

    /**
     * @param string $data
     * @return bool
     */
    public static function remote_procedure_call_success(string $data): bool {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $response = self::phpunit_get($data);
            if (!is_null($response)) {
                return true;
            }

            return false;
        }

        // If input is empty, it just return false.
        if (empty(trim($data))) {
            return false;
        }

        // If the input is still old config, we just return true.
        $old_config = get_config('totara_msteams', 'domain_name');
        if (!empty($old_config) && $old_config === $data) {
            return true;
        }

        if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
            return true;
        }

        $api = new api();
        /** @var response $response */
        $response = $api->get($api->get_tenant_id_endpoint_url($data));
        if (!$response->is_ok()) {
            return false;
        }

        $response = $response->get_body_as_json();
        $tenant_id = self::get_tenant_id($response);

        if (is_null($tenant_id)) {
            return false;
        }

        return self::call_gateway($api, $tenant_id);
    }

    /**
     * @param api $api
     * @param string $tenant_id
     * @return bool
     */
    public static function call_gateway(api $api, string $tenant_id): bool {
        global $CFG;

        if (!isset($CFG->msteams_gateway_private_key)) {
            throw new coding_exception("The gateway private key is not set");
        }

        if (!extension_loaded("openssl")) {
            throw new coding_exception("PHP OpenSSL extension is not enabled");
        }

        $form_data = [
            "TenantId" => $tenant_id,
            "SiteUrl" => $CFG->wwwroot
        ];

        // Memory leakable code in lower than PHP 8.0
        $private_key = openssl_pkey_get_private($CFG->msteams_gateway_private_key);
        $json_data = json_encode($form_data);
        $signature = null;

        openssl_sign($json_data, $signature, $private_key, OPENSSL_ALGO_SHA512);
        $form_data["Signature"] = base64_encode($signature);

        $response = $api->post($api->get_gateway_url(), new formdata($form_data));
        return $response->is_ok();
    }

    /**
     * @param string $data
     * @return array|null
     */
    private static function phpunit_get(string $data): ?array {
        if (class_exists('mock_http_request')) {
            return \mock_http_request::get_body($data);
        }

        return null;
    }
}