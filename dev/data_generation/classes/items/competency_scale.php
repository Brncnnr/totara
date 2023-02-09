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

use degeneration\App;
use degeneration\Cache;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;

class competency_scale extends item {

    /**
     * Array of scale values
     *
     * @var scale_value[]
     */
    protected $values = [];

    /**
     * Flag to check whether it has a min proficient value
     *
     * @var bool
     */
    protected $has_min_proficient_value = false;

    /**
     * Table name of the item to generate
     *
     * @return string
     */
    public function get_entity_class(): string {
        return scale::class;
    }

    /**
     * Add a value to a given scale
     *
     * @param bool $proficient
     * @param string|null $name
     * @param scale_value $value
     * @param string|null $description
     *
     * @return $this
     */
    public function add_value(bool $proficient, ?string $name = null, ?scale_value &$value = null, ?string $description = null) {
        $props = $this->get_value_properties();

        if (!$proficient && $this->has_min_proficient_value) {
            throw new \Exception('This scale already has a min proficient value, you can not a value that is not proficient anymore');
        }

        $props['proficient'] = $proficient;

        if (!empty($name)) {
            $props['name'] = $name;
        }

        if (!empty($description)) {
            $props['description'] = $description;
        }

        $this->values[] = $value = new scale_value($props);

        if ($proficient && !$this->has_min_proficient_value) {
            $this->has_min_proficient_value = $value;
        }

        return $this;
    }

    /**
     * Save scale values
     *
     * @return $this
     */
    protected function save_values() {
        if (!$this->data instanceof scale || !$this->data->exists()) {
            throw new \Exception('You can save values only for scales that already exist');
        }

        if (empty($this->values)) {
            throw new \Exception('A scale must have at least one value');
        }

        $i = 1;

        for ($c = count($this->values) - 1; $c >= 0; $c--) {
            $this->values[$c]->sortorder = $i;
            $i++;
            $this->data->values()->save($this->values[$c]);
        }

        return $this;
    }

    /**
     * Get properties for a scale value
     *
     * @return array
     */
    public function get_value_properties() {
        return [
            'name' => App::faker()->catchPhrase,
            'description' => App::faker()->bs,
            'timemodified' => time(),
            'usermodified' => 0,
        ];
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function get_properties(): array {
        return [
            'name' => App::faker()->catchPhrase,
            'description' => App::faker()->bs,
            'timemodified' => time(),
            'usermodified' => 0,
        ];
    }

    /**
     * Save a user
     *
     * @return bool
     */
    public function save(): bool {
        $properties = [];

        foreach ($this->get_properties() as $key => $property) {
            $properties[$key] = $this->evaluate_property($property);
        }

        $this->data = new scale($properties);
        $this->data->save();
        $this->save_values();

        $this->data->minproficiencyid = $this->has_min_proficient_value->id;
        $this->data->defaultid = $this->has_min_proficient_value->id;
        $this->data->save();

        Cache::get()->add($this);

        return true;
    }

}