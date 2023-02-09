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

namespace degeneration\items;

use core\orm\entity\entity;
use degeneration\Cache;

abstract class item {

    /**
     * Saved data
     *
     * @var mixed
     */
    protected $data = null;

    /**
     * Table name of the item to generate
     *
     * @return string
     */
    public function get_table(): string {
        return $this->get_entity_class()::TABLE;
    }

    /**
     * Get list of properties to be added to the generated item
     *
     * @return array
     */
    abstract public function get_properties(): array;

    /**
     * Save a given item to the database
     *
     * @return bool
     */
    public function save(): bool {
        $class = $this->get_entity_class();

        $entity = new $class();

        foreach ($this->get_properties() as $key => $property) {
            $entity->set_attribute($key, $this->evaluate_property($property));
        }

        $entity->save();
        
        $this->data = $entity;

        Cache::get()->add($this);

        return true;
    }

    /**
     * Save item and return itself
     *
     * @return $this
     */
    public function save_and_return() {
        $this->save();

        return $this;
    }

    /**
     * Get entity class name
     *
     * @return string
     */
    public function get_entity_class(): ?string {
        return null;
    }

    /**
     * New up $class
     *
     * @param string $class
     * @return item
     */
    public static function create(string $class): item {
        if (!is_subclass_of(static::class, $class)) {
            throw new \Exception("'$class' must inherit 'item'");
        }

        return new $class();
    }

    /**
     * Return created data
     *
     * @param string|null $property Property to get
     * @return mixed|entity
     */
    public function get_data(?string $property = null) {
        if ($property) {
            return $this->data->{$property} ?? null;
        }

        return $this->data;
    }

    /**
     * Evaluate property
     *
     * @param $value
     * @return mixed
     */
    protected function evaluate_property($value) {
        if ($value instanceof \Closure) {
            return $value($this);
        }

        return $value;
    }

    /**
     * Evaluate properties
     *
     * @return mixed[]
     */
    protected function evaluate_properties() {
        $keys = array_keys($this->get_properties());
        $values = array_map([$this, 'evaluate_property'], $this->get_properties());

        return array_combine($keys, $values);
    }

    /**
     * Constructor
     *
     * @return static
     */
    public static function new() {
        return new static();
    }
}