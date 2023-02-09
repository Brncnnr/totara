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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\performance_statistics;

use core_component;
use stdClass;

/**
 * Class which collects performance stats data from throughout the application.
 *
 * @package core\performance_statistics
 */
class collector {

    /**
     * @var provider[];
     */
    protected $providers = [];

    public function __construct() {
        /** @var provider[] $providers */
        $providers = core_component::get_namespace_classes('performance_statistics', provider::class);
        foreach ($providers as $provider) {
            $this->providers[] = new $provider();
        }
    }

    /**
     * Returns all the data collected.
     *
     * @return stdClass
     */
    public function all(): stdClass {
        $data = new stdClass();

        foreach ($this->providers as $provider) {
            $component = $provider->get_component();
            if (!isset($data->{$component})) {
                $data->{$component} = new stdClass();
            }
            $data->{$component}->{$provider->get_key()} = $provider->get_data();
        }

        return $data;
    }

}