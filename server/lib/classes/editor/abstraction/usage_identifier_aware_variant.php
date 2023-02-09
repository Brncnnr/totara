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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\editor\abstraction;

use totara_core\identifier\component_area;

/**
 * This interface is to help the process to inject the information
 * related to the usage identifier to the instance itself and can pass it down
 * to a lower additional options of variant.
 */
interface usage_identifier_aware_variant {
    /**
     * @param component_area $component_area
     * @return void
     */
    public function set_component_area(component_area $component_area): void;

    /**
     * @return component_area
     */
    public function get_component_area(): component_area;

    /**
     * @param int $instance_id
     * @return void
     */
    public function set_instance_id(int $instance_id): void;

    /**
     * @return int|null
     */
    public function get_instance_id(): ?int;
}