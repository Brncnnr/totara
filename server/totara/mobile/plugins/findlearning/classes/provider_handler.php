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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

namespace mobile_findlearning;

use mobile_findlearning\config;
use totara_catalog\provider_handler as core_handler;

defined('MOODLE_INTERNAL') || die();

class provider_handler extends core_handler {

    /** @var provider_handler */
    private static $instance;

    /** @var provider[] */
    private $allproviderclasses = null;

    /** @var provider[] */
    private $activeproviderclasses = null;

    /** @var provider[] */
    private $activeproviders = null;

    /**
     * Return a singleton instance.
     *
     * @return provider_handler
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __construct() {
        // This just needs to exist for instance().
    }


    /**
     * Gets an array of all provider class names that are active in the catalog, indexed by objecttype.
     *
     * Excludes providers that are not available because the plugin they belong to is disabled.
     *
     * @return string[]
     */
    private function get_active_provider_classes() {

        if (empty($this->activeproviderclasses)) {
            $providernames = $this->get_all_provider_classes();

            $activeproviderclasses = [];

            /** @var provider $providername */
            foreach ($providernames as $key => $providername) {
                if (!config::instance()->is_provider_active($providername::get_object_type())) {
                    continue;
                }

                $activeproviderclasses[$providername::get_object_type()] = $providername;
            }

            $this->activeproviderclasses = $activeproviderclasses;
        }

        return $this->activeproviderclasses;
    }

    /**
     * Get all active providers, indexed by objecttype.
     *
     * Excludes providers that are not available because the plugin they belong to is disabled.
     *
     * @return provider[]
     */
    public function get_active_providers() {
        if (is_null($this->activeproviders)) {
            $providernames = $this->get_active_provider_classes();

            $providers = [];

            /** @var provider $providername */
            foreach ($providernames as $providername) {
                /** @var provider $provider */
                $provider = new $providername();

                $object_type = $provider::get_object_type();

                $providers[$object_type] = $provider;
            }

            $this->activeproviders = $providers;
        }

        return $this->activeproviders;
    }

    /**
     * Determine if the specified provider is active or not.
     *
     * @param string $objecttype
     * @return bool
     */
    public function is_active(string $objecttype) {
        $providers = $this->get_active_provider_classes();

        return !empty($providers[$objecttype]);
    }

    /**
     * Gets one of the active providers.
     *
     * Inactive providers should not be instantiated, and trying to get one will result in an exception.
     *
     * @param string $objecttype
     * @return provider
     */
    public function get_provider(string $objecttype) {
        $providers = $this->get_active_providers();

        if (empty($providers[$objecttype])) {
            throw new \coding_exception("Tried to get instance of a catalog provider that wasn't found: " . $objecttype);
        }

        return $providers[$objecttype];
    }

}
