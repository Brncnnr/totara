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

namespace mod_approval\model\workflow\stage_feature;

use mod_approval\model\workflow\workflow_stage;

/**
 * Base class representing contract of a stage feature.
 *
 * Properties:
 *
 * @property-read string $label Label of stage feature
 * @property-read string $enum Enum of stage feature
 */
abstract class base {

    /**
     * Workflow stage instance.
     *
     * @var workflow_stage
     */
    protected $stage;

    public function __construct(workflow_stage $stage) {
        $this->stage = $stage;
    }

    /**
     * Name of the feature.
     *
     * @return string
     */
    abstract public static function get_label(): string;

    /**
     * Enum representing the feature.
     *
     * @return string
     */
    abstract public static function get_enum(): string;

    /**
     * Sort order of the feature when listed.
     *
     * @return int
     */
    abstract public static function get_sort_order(): int;

    /**
     * Allows the stage feature to add default objects to the stage when it is created.
     */
    abstract public function add_default(): void;

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