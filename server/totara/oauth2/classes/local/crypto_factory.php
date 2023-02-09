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
namespace totara_oauth2\local;

use coding_exception;
use totara_oauth2\config;
use context_system;
use stdClass;

/**
 * A factory class that help us to generate a pair of encryption keys and stored in our file storage system.
 * These keys are playing a part of providing DSA for JWT, and at the moment we don't share this key to external
 * parties, but to only use it internally.
 *
 * Ideally it will generate the key, when it is being requested. However, user/admin is able to override this
 * via the config of plugin.
 */
class crypto_factory {
    /**
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * @return string
     */
    private static function default_private_key_file_path(): string {
        global $CFG;
        return "{$CFG->dataroot}/totara/oauth2/private_key.pem";
    }

    /**
     * @return string
     */
    private static function default_public_key_file_path(): string {
        global $CFG;
        return "{$CFG->dataroot}/totara/oauth2/public_key.pem";
    }

    /**
     * @return void
     */
    private static function prepare_directory(): void {
        global $CFG;
        $dir_path = "{$CFG->dataroot}/totara/oauth2";

        if (!file_exists($dir_path)) {
            // I am not so sure if this function is allow to be called here, as it was marked private.
            // However, it was being used not just within setuplib.php, hence it drove me to this decision.
            make_writable_directory($dir_path);
        }
    }

    /**
     * @return void
     */
    private static function generate_pair_of_keys(): void {
        if (!extension_loaded("openssl")) {
            throw new coding_exception("Extension openssl is not enabled");
        }

        $private_key_path = self::default_private_key_file_path();
        $public_key_path = self::default_public_key_file_path();

        if (file_exists($private_key_path) && file_exists($public_key_path)) {
            // We only skip when two keys are existing. Otherwise, if one missing or none
            // are missing then we regenerate them.
            return;
        }

        // Prepare the directory first.
        self::prepare_directory();

        $private_key = openssl_pkey_new();
        if (false === $private_key) {
            throw new coding_exception("Cannot create a new private key");
        }

        $private_key_content = null;
        openssl_pkey_export($private_key, $private_key_content);

        // Export the private key into a file.
        file_put_contents($private_key_path, $private_key_content);

        // Export the public key into a file.
        $public_key_content = openssl_pkey_get_details($private_key)["key"];
        file_put_contents($public_key_path, $public_key_content);
    }

    /**
     * Returns the private key file path if the key exist from config.
     * Otherwise, generate one and stores it, then returns to the external caller.
     *
     * @return string
     */
    public static function get_private_key_file_path(): string {
        $config_private_key = config::get_private_key_path();
        if (!empty($config_private_key)) {
            return $config_private_key;
        }

        self::generate_pair_of_keys();
        return self::default_private_key_file_path();
    }

    /**
     * Returns the public key file path if the key exist from config.
     * Otherwise, generate one and stores it, then returns to the external caller.
     *
     * @return string
     */
    public static function get_public_key_file_path(): string {
        $config_public_key = config::get_public_key_path();
        if (!empty($config_public_key)) {
            return $config_public_key;
        }

        self::generate_pair_of_keys();
        return self::default_public_key_file_path();
    }

    /**
     * Fetch the encryption key, or generate a new one if it doesn't already exist.
     *
     * @return string
     */
    public static function get_encryption_key(): string {
        $encryption_key = config::get_encryption_key();
        if (empty($encryption_key)) {
            $encryption_key = base64_encode(random_bytes(32));
        }

        config::set_encryption_key($encryption_key);
        return $encryption_key;
    }
}