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
 * @package totara_core
 */
namespace totara_core\identifier;

/**
 * A metadata class that tracks a specific item instance,
 * where an item is a database record made up of a context, component, area and id.
 *
 * Useful for updates/deletes to an existing record.
 * If a new record is being created, use {@see component_area} instead.
 */
class instance_identifier {
    /**
     * @var int
     */
    private $instance_id;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @var component_area
     */
    private $component_area;

    /**
     * instance_identifier constructor.
     * @param component_area $component_area
     * @param int            $instance_id
     * @param int            $context_id
     */
    public function __construct(component_area $component_area, int $instance_id, int $context_id) {
        $this->instance_id = $instance_id;
        $this->component_area = $component_area;
        $this->context_id = $context_id;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->component_area->get_component();
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return $this->component_area->get_area();
    }

    /**
     * @return int
     */
    public function get_instance_id(): int {
        return $this->instance_id;
    }

    /**
     * @return int
     */
    public function get_context_id(): int {
        return $this->context_id;
    }
}