<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;


class basic_model {

    /**
     * @var array
     */
    protected $attributes;

    /**
     * Magic attribute getter
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Required to use it in GraphQL implementation as it checks first whether the field is set
     * @param $name
     *
     * @return bool
     */
    public function __isset($name): bool {
        return isset($this->attributes[$name]);
    }

    /**
     * Internal helper to set attributes on a model
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    protected function set_attribute(string $name, $value) {
        $this->attributes[$name] = $value;

        return $this;
    }

}