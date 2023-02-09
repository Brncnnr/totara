<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

namespace mobile_findlearning;

use totara_catalog\local\config as core_config;
use mobile_findlearning\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Extend and overwrite the catalog config for mobile.
 */
class config extends core_config {

    private static $instance;

    private $config_cache = null;

    private $provider_config_cache = [];

    private $provider_defaults_cache = null;

    private $learningtypesincatalog = null;

    /**
     * Return a singleton instance.
     *
     * @return config
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    /**
     * Get config from DB and unserialize values.
     *
     * This will always return a complete catalog configuration array.
     * If settings are not found in DB, they will be filled with default values.
     *
     * Application logic should not assume that everything is consistent and should handle dependencies between
     * config settings. E.g. it's possible that an admin configures placeholders for 'details_description' text fields
     * but then disables 'details_description', which still leaves the placeholder configuration in place (but meaningless
     * for the time being).
     *
     * @return array
     */
    public function get(): array {
        if (is_null($this->config_cache)) {
            $config_db = (array)get_config('mobile_findlearning');
            $defaults = parent::get();

            // Filter everything out that happens to be in plugin config but that is not for our purpose (e.g. 'version').
            $config_db = array_filter(
                $config_db,
                function ($k) use ($defaults) {
                    return isset($defaults[$k]);
                },
                ARRAY_FILTER_USE_KEY
            );

            $config_db = $this->unserialize_values($config_db);
            $this->config_cache = array_merge($defaults, $config_db);
        }

        return $this->config_cache;
    }

    /**
     * Get an array of the active objecttypes.
     * 1) Return mobile enabled items
     * 2) Return core enabled items as default (web setup but mobile not)
     * 3) Return all available items as final default (neither set up)
     *
     * @return string[]
     */
    public function get_learning_types_in_catalog() {
        if (!is_array($this->learningtypesincatalog)) {
            // First load all core types available, default to all types if not set.
            $availabletypes = json_decode(get_config('totara_catalog', 'learning_types_in_catalog'));
            if (!is_array($availabletypes)) {
                $availabletypes = [];

                foreach (provider_handler::instance()->get_all_provider_classes() as $providerclass) {
                    $availabletypes[] = $providerclass::get_object_type();
                }
            }

            // Now load all learning types enabled by the mobile catalog.
            // Note: This would be good to have as an admin setting which would look something like this:
            //       $mobiletypes = json_decode(get_config('mobile_findlearning', 'learning_types_in_catalog'));
            $mobiletypes = ['course', 'playlist', 'engage_article'];
            if (is_array($mobiletypes)) {
                // both have been set up, compare to make sure we don't try to access non-existant data.
                $this->learningtypesincatalog = [];
                foreach ($mobiletypes as $type) {
                    if (in_array($type, $availabletypes)) {
                        $this->learningtypesincatalog[] = $type;
                    }
                }
            } else {
                // Looks like mobile isn't overriding anything, just return all available types.
                $this->learningtypesincatalog = $availabletypes;
            }
        }

        return $this->learningtypesincatalog;
    }

    /**
     * @param array $config
     * @return array
     */
    private function unserialize_values(array $config): array {
        foreach ($config as $key => &$value) {
            $value = json_decode($value, true);
        }
        return $config;
    }

    /**
     * Determines if a provider is active. If the config is not set then it defaults to all providers being enabled.
     *
     * @param string $objecttype
     * @return bool
     */
    public function is_provider_active(string $objecttype): bool {
        return in_array($objecttype, $this->get_learning_types_in_catalog());
    }
}
