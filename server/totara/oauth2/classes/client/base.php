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
 * @package totara_oauth2
 */

namespace totara_oauth2\client;

use totara_oauth2\entity\client_provider;

/**
 * Client base class will be extended by the component in the system that want to integrate with oauth2 provider
 * internally to define functions such as capabilities check or other methods based on own needs.
 */
abstract class base {
    /**
     * @var string
     */
    protected $component;

    /**
     * Keeping the constructor simples
     * base constructor.
     */
    final public function __construct() {
        $cls = get_called_class();
        $parts = explode("\\", $cls);

        $this->component = reset($parts);
    }

    /**
     * @return string
     */
    final public function get_component(): string {
        return $this->component;
    }

    /**
     * Given the ability to check by the plugin that extend this client and the default is true
     *
     * @param client_provider $client_provider
     * @return bool
     */
     public function can_create_token(client_provider $client_provider): bool {
         return true;
     }
}