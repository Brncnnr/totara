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
namespace totara_oauth2;

use core\base_plugin_config;

class config extends base_plugin_config {
    /**
     * @var string
     */
    public const XAPI_WRITE = "xapi:write";

    /**
     * Default constant value is for how many times we look up database to generate client id and client
     * secret.
     *
     * @var int
     */
    public const MAX_GENERATION_ATTEMPTS = 5;

    /**
     * @return string
     */
    protected static function get_component(): string {
        return "totara_oauth2";
    }

    /**
     * Returns the file path to the private key which is being used to encrypt the token.
     *
     * @return string|null
     */
    public static function get_private_key_path(): ?string {
        return self::get("private_key_path");
    }

    /**
     * Returns the file path to the public key which is being used to decrypt the token.
     *
     * @return string|null
     */
    public static function get_public_key_path(): ?string {
        return self::get("public_key_path");
    }

    /**
     * The encryption we are using to help generate the oauth2 access token.
     * Note that null will be returned when no value is presenting, and the functionality
     * would be broken due to no value.
     *
     * @return string
     */
    public static function get_encryption_key(): ?string {
        return self::get("encryption_key");
    }

    /**
     * @param string $key
     * @return void
     */
    public static function set_encryption_key(string $key): void {
        self::set("encryption_key", $key);
    }

    /**
     * @param string $value
     * @return string|null
     */
    public static function get_scope_type_value(string $value): ?string {
        $scopes = self::valid_scopes();

        return $scopes[$value] ?? null;
    }

    /**
     * @return array|string[]
     */
    private static function valid_scopes(): array {
        return [
            'XAPI_WRITE' => self::XAPI_WRITE
        ];
    }
}