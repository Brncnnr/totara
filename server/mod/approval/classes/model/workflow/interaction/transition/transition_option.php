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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\workflow\interaction\transition;

/**
 * Transition option implementation that resolves stages in a workflow.
 */
class transition_option {
    /**
     * Stage name/Name of transition
     *
     * @var string
     */
    private $name;

    /**
     * Stage id or enum of transition
     *
     * @var string
     */
    private $value;

    /**
     * Optional information about transition
     *
     * @var string
     */
    private $data;

    public function __construct(string $name, string $value, ?string $data = null) {
        $this->name = $name;
        $this->value = $value;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_value(): string {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function get_data(): ?string {
        return $this->data;
    }

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