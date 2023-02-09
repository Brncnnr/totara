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
use hierarchy_organisation\entity\organisation_framework;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\hierarchy_framework;

abstract class hierarchy_item extends item {

    /**
     * Hierarchy item framework entity
     *
     * @var null|hierarchy_framework
     */
    protected $framework = null;

    /**
     * Link to another hierarchy item
     *
     * @var null|static
     */
    protected $parent = null;

    /**
     * Saved data
     *
     * @var mixed
     */
    protected $data = null;

    /**
     * Set framework for a given hierarchy item
     *
     * @param hierarchy_framework $framework
     * @return $this
     */
    public function set_framework(hierarchy_framework $framework) {
        $this->framework = $framework;

        return $this;
    }

    /**
     * Set parent item
     *
     * @param hierarchy_item|null $parent
     * @return $this
     */
    public function set_parent(?self $parent) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return $this|null
     */
    public function get_parent(): ?self {
        return $this->parent;
    }

    /**
     * Get entity class
     *
     * @return string|null
     */
    public function get_entity_class(): ?string {
        throw new \Exception('You must specify entity class');
    }

    /**
     * Save the thing to the database
     *
     * @return bool
     */
    public function save(): bool {

        if ($this->parent) {
            if (is_null($this->framework)) {
                $framework_class = $this->get_framework_entity_class();

                $this->set_framework(new $framework_class($this->parent->get_data()->frameworkid));
            } else {
                if ($this->parent->get_data()->frameworkid !== $this->framework->id) {
                    throw new \Exception('Hierarchy item parent must have the same framework specified');
                }
            }
        }

        if (!$this->framework) {
            $framework_class = $this->get_framework_entity_class();

            $this->set_framework(new $framework_class(static::generator()->create_framework($this->get_type())));
        }

        $class = $this->get_entity_class();

        $record = [];

        $record['parentid'] = $this->get_parent() ? ($this->get_parent()->get_data('id') ?? 0) : 0;

        foreach ($this->get_properties() as $key => $property) {
            $record[$key] = $this->evaluate_property($property);
        }

        $this->data = new $class(
            static::generator()->create_hierarchy(
                $this->framework->id,
                $this->get_type(),
                $record,
                false
            )
        );

        Cache::get()->add($this);

        return true;
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
     * Get hierarchy item type
     *
     * @return string
     */
    abstract public function get_type(): string;

    /**
     * Create a framework for a given hierarchy item
     *
     * @param array $record
     * @return hierarchy_framework
     */
    public function create_framework(array $record = []): hierarchy_framework {
        $record = array_merge([
            'fullname' => App::faker()->catchPhrase,
            'description' => App::faker()->bs,
        ], $record);

        $fw = static::generator()->create_framework($this->get_type(), $record);
        $class = $this->get_framework_entity_class();

        return new $class($fw);
    }

    /**
     * Get framework entity class name
     *
     * @return string
     */
    public function get_framework_entity_class(): string {
        switch ($this->get_type()) {
            case 'competency':
                return competency_framework::class;

            case 'position':
                return \hierarchy_position\entity\position_framework::class;

            case 'organisation':
                return organisation_framework::class;

            default:
                throw new \Exception('Hierarchy type "' . $this->get_type() . '" is not supported yet...');
        }
    }

    /**
     * Get hierarchy generator
     *
     * @return \totara_hierarchy\testing\generator
     */
    public static function generator() {
        return \totara_hierarchy\testing\generator::instance();
    }
}