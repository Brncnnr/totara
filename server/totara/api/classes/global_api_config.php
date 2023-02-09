<?php
/**
 * This file is part of Totara Learn
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

namespace totara_api;

use GraphQL\Error\DebugFlag;

/**
 * Totara API configuration settings and methods.
 */
class global_api_config {

    /**
     * @return int|null
     */
    public static function get_site_rate_limit(): ?int {
        $site_rate_limit = get_config('totara_api', 'site_rate_limit');
        return $site_rate_limit !== false ? $site_rate_limit : null;
    }

    /**
     * @return int|null
     */
    public static function get_client_rate_limit(): ?int {
        $client_rate_limit = get_config('totara_api', 'client_rate_limit');
        return $client_rate_limit !== false ? $client_rate_limit : null;
    }

    /**
     * @return int|null
     */
    public static function get_max_query_complexity(): ?int {
        $max_query_complexity = get_config('totara_api', 'max_query_complexity');
        return $max_query_complexity !== false ? $max_query_complexity : null;
    }

    /**
     * @return int|null
     */
    public static function get_max_query_depth(): ?int {
        $max_query_depth = get_config('totara_api', 'max_query_depth');
        return $max_query_depth !== false ? $max_query_depth : null;
    }

    /**
     * @return int|null
     */
    public static function get_default_token_expiration(): ?int {
        $default_token_expiration = get_config('totara_api', 'default_token_expiration');
        return $default_token_expiration !== false ? $default_token_expiration : null;
    }

    /**
     * @return bool
     */
    public static function get_enable_introspection(): bool {
        return get_config('totara_api', 'enable_introspection');
    }

    /**
     * @return bool
     */
    public static function get_disable_oauth2_authentication(): bool {
        return get_config('totara_api', 'disable_oauth2_authentication');
    }

    /**
     * @return array
     */
    public static function get_settings_map(): array {
        return [
            'site_rate_limit' => self::get_site_rate_limit(),
            'client_rate_limit' => self::get_client_rate_limit(),
            'max_complexity_cost' => self::get_max_query_complexity(),
            'default_token_expiry_time' => self::get_default_token_expiration(),
            'response_debug' => response_debug::get_string(self::get_response_debug())
        ];
    }

    /**
     * Returns the 'hidden config' setting for 'response_debug' error reporting level, i.e. this will be
     * set in the server root config.php file, $CFG->forced_plugin_settings['totara_api'] setting, and not in the config_plugins
     * table.
     * I.e. determines the amount of information returned by an API response when an error occurs.
     * @return int
     */
    public static function get_response_debug(): int {
        $response_debug_level = get_config('totara_api', 'response_debug');
        return $response_debug_level  !== false ? $response_debug_level : response_debug::ERROR_RESPONSE_LEVEL_NORMAL;
    }

    /**
     * Based on a config for 'response_debug', returns a value mapped to a graphql-php DebugFlag
     * setting. This will be the error reporting level in External API responses.
     * @param int|null $debug_level - this could be a value like ERROR_RESPONSE_LEVEL_NORMAL, i.e. 1.
     * @return int
     */
    public static function get_response_debug_flag(?int $response_debug_setting = null): int {
        if (is_null($response_debug_setting)) {
            $response_debug_setting = self::get_response_debug();
        }

        switch ($response_debug_setting) {
            case response_debug::ERROR_RESPONSE_LEVEL_NONE:
                $response_debug_flag = DebugFlag::NONE;
                break;
            case response_debug::ERROR_RESPONSE_LEVEL_NORMAL:
                $response_debug_flag = DebugFlag::INCLUDE_DEBUG_MESSAGE;
                break;
            case response_debug::ERROR_RESPONSE_LEVEL_DEVELOPER:
                $response_debug_flag = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
                break;
            default:
                $response_debug_flag = DebugFlag::INCLUDE_DEBUG_MESSAGE;
        }
        return $response_debug_flag;
    }
}