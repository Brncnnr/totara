<?php
/**
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */
namespace mod_approval\model\assignment\assignment_type;

use core\orm\entity\entity;

/**
 * Base class for assignment type
 */
abstract class base {

    /**
     * @var entity
     */
    protected $entity;

    /**
     * Get human-readable label
     *
     * @return string
     */
    abstract public static function get_label(): string;

    /**
     * Get code
     *
     * @return int
     */
    abstract public static function get_code(): int;

    /**
     * Get enum
     *
     * @return string
     */
    abstract public static function get_enum(): string;

    /**
     * Get sort order
     *
     * @return int
     */
    abstract public static function get_sort_order(): int;

    /**
     * Get entity table name
     *
     * @return string
     */
    abstract public static function get_table(): string;

    /**
     * Get instance of assignment type
     *
     * @param int $id
     *
     * @return base
     */
    abstract public static function instance(int $id): base;

    /**
     * Get entity related to the assignment type
     *
     * @return entity
     */
    public function get_entity(): entity {
        return $this->entity;
    }

    /**
     * Get module name
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Get module id_number
     *
     * @return string
     */
    abstract public function get_id_number(): string;
}