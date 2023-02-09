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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw;

use coding_exception;
use stdClass;

/**
 * A serialisable object.
 */
abstract class persist_object {
    /** @var array */
    private $data;

    /** @var activity|null */
    private $activity = null;

    /**
     * Resolve converter for the field.
     *
     * @param string $name
     * @return string string, boolean, integer, time, url or a convertible class name
     */
    protected abstract static function get_mapper(string $name): string;

    /**
     * Get an activity associated to this object.
     *
     * @return activity
     * @throws coding_exception
     */
    public function get_activity(): activity { // not nullable
        if ($this->activity === null) {
            throw new coding_exception('activity is not available for this object.');
        }
        return $this->activity;
    }

    /**
     * Deserialise.
     *
     * @param stdClass $data
     * @param activity $owner
     * @return self
     */
    public static function from_object(stdClass $data, activity $owner = null): self {
        $self = new static();
        $self->data = [];
        foreach ((array)$data as $name => $value) {
            $self->data[$name] = $self->map_value($name, $value);
        }

        $self->activity = $owner;
        return $self;
    }

    /**
     * Serialise.
     *
     * @return stdClass
     */
    public function to_object(): stdClass {
        $data = new stdClass();
        foreach ((array)$this->data as $name => $value) {
            $data->{$name} = $this->unmap_value($name, $value);
        }
        return $data;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    private function map_value(string $name, $value) {
        $mapper = static::get_mapper($name);
        if (is_subclass_of($mapper, convertible::class)) {
            return $mapper::convert_from($value, $this);
        }
        if ($mapper === 'string') {
            return (string)$value;
        } else if ($mapper === 'boolean') {
            return (bool)$value;
        } else if ($mapper === 'integer') {
            return (int)$value;
        } else if ($mapper === 'url') {
            return clean_param($value, PARAM_URL);
        } else if ($mapper === 'time') {
            return new \DateTime('@'.strtotime($value));
        }
        return $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    private function unmap_value(string $name, $value) {
        $mapper = static::get_mapper($name);
        if (is_subclass_of($mapper, convertible::class)) {
            return $mapper::convert_to($value);
        }
        if ($mapper === 'string' || $mapper === 'boolean' || $mapper === 'integer' || $mapper === 'url') {
            return $value;
        } else if ($mapper === 'time') {
            /** @var \DateTime $value */
            return $value->format('Y-m-d\TH:i:s.v\Z');
        }
        return $value;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function __isset(string $name): bool {
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        if (strpos($name, 'get_') === 0) {
            $name = substr($name, 4);
            if (!isset($this->data[$name])) {
                throw new \Exception("property '{$name}' not found");
            }
            return $this->data[$name];
        }
        if (strpos($name, 'set_') === 0) {
            $name = substr($name, 4);
            $this->data[$name] = $arguments[0];
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        if (!isset($this->data[$name])) {
            throw new \Exception("property '{$name}' not found");
        }
        return $this->data[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value): void {
        $this->data[$name] = $value;
    }
}
