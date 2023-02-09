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

namespace mod_approval\model\workflow\stage_type;

use mod_approval\model\workflow\stage_type\state_manager\base as state_manager;
use mod_approval\model\workflow\workflow_stage;

/**
 * Base class for stage type.
 *
 * Properties:
 *
 * @property-read string $label Label of stage type
 * @property-read string $enum Enum of stage type
 * @property-read int $code Code of stage type
 * @property-read array $features Features supported by the stage type
 */
abstract class base {

    /**
     * Get label
     * @return string
     */
    abstract public static function get_label(): string;

    /**
     * Get code used to represent stage type when stored in the database.
     *
     * @return int
     */
    abstract public static function get_code(): int;

    /**
     * Get ENUM identifying the stage type.
     *
     * @return string
     */
    abstract public static function get_enum(): string;

    /**
     * Get sort order in which the type appears on the list.
     *
     * @return int
     */
    abstract public static function get_sort_order(): int;

    /**
     * List of stage features that can be configured for the stage type.
     * Represents the order in which the features appear.
     *
     * @return array
     */
    abstract public static function get_configured_features(): array;

    /**
     * List of application actions that can be used with this stage type.
     *
     * @return array
     */
    abstract public static function get_available_actions(): array;

    /**
     * Application state manager for the stage type.
     *
     * @param workflow_stage $workflow_stage
     * @return state_manager
     */
    abstract public static function state_manager(workflow_stage $workflow_stage): state_manager;

    /**
     * Magic attribute getter
     *
     * @param string $field
     * @return mixed|null
     */
    public function __get(string $field) {
        $get_method = 'get_' . $field;

        return method_exists($this, $get_method)
            ? $this->$get_method()
            : null;
    }
}
