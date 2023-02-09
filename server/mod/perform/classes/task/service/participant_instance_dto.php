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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task\service;

use coding_exception;

/**
 * A simple readonly object to transfer data from one service to another.
 *
 * This avoids a full entity to be passed down via hooks to watchers we might not
 * have control over.
 *
 * @property-read int $id
 * @property-read int $activity_id
 * @property-read int $core_relationship_id
 */
class participant_instance_dto {

    /** @var int */
    protected $id;

    /** @var int */
    protected $activity_id;

    /** @var int */
    protected $core_relationship_id;

    /**
     * Create a new dto from a given entity.
     *
     * @param array $data
     * @return self
     */
    public static function create_from_data(array $data): self {
        $instance = new self(
            $data['id'],
            $data['activity_id'],
            $data['core_relationship_id']
        );

        return $instance;
    }

    private function __construct(
        int $id,
        int $activity_id,
        int $core_relationship_id
    ) {
        $this->id = $id;
        $this->activity_id = $activity_id;
        $this->core_relationship_id = $core_relationship_id;
    }

    /**
     * Get participant instance id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get activity id.
     *
     * @return int
     */
    public function get_activity_id(): int {
        return $this->activity_id;
    }

    /**
     * Get core relationship id.
     *
     * @return int
     */
    public function get_core_relationship_id(): int {
        return $this->core_relationship_id;
    }

    final public function __get($name) {
        $getter_name = "get_{$name}";
        if (method_exists($this, $getter_name)) {
            return $this->{$getter_name}();
        }

        throw new coding_exception('Unknown getter method for '.$name);
    }

    final public function __set($name, $value) {
        throw new coding_exception('This dto is ready-only and cannot be modified');
    }

    final public function __isset($name) {
        return method_exists($this, "get_{$name}");
    }

}