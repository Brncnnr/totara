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

namespace degeneration;

use degeneration\items\hierarchy_item;
use degeneration\items\item;
use totara_hierarchy\entity\hierarchy_framework;

class hierarchy {

    /**
     * Depth of the hierarchy to create
     *
     * @var int
     */
    protected $depth = 1;

    /**
     * Count of items to create on a hierarchy level
     *
     * @var int
     */
    protected $count = 1;

    /**
     * Type of hierarchy to create
     *
     * @var string
     */
    protected $type = null;

    /**
     * Framework entity to create
     *
     * @var hierarchy_framework[]
     */
    protected $frameworks = [];

    /**
     * Use a random variable depth for this hierarchy, e.g rand(1, $depth)
     *
     * @var bool
     */
    protected $variable_depth = false;

    /**
     * Use a random variable count of items for this hierarchy level, e.g rand(1, $count)
     *
     * @var bool
     */
    protected $variable_count = false;

    /**
     * Created items in this hierarchy
     *
     * @var array
     */
    protected $items = [];

    /**
     * Get all items created in this hierarchy
     *
     * @return array
     */
    public function get_items(): array {
        return $this->items;
    }

    /**
     * Set variable depth for the hierarchy
     *
     * @param bool $variable
     * @return $this
     */
    public function set_variable_depth(bool $variable = true) {
        $this->variable_depth = $variable;

        return $this;
    }

    /**
     * Set variable count at the hierarchy level
     *
     * @param bool $variable
     * @return $this
     */
    public function set_variable_count(bool $variable = true) {
        $this->variable_count = $variable;

        return $this;
    }

    /**
     * Set depth for created items
     *
     * @param int $depth
     * @return $this
     */
    public function set_depth(int $depth) {
        if ($depth < 1) {
            throw new \Exception('Depth can not be lower than 1');
        }

        $this->depth = $depth;

        return $this;
    }

    /**
     * Set count for created items
     *
     * @param int $count
     * @return $this
     */
    public function set_count(int $count) {
        if ($count < 1) {
            throw new \Exception('Count can not be lower than 1');
        }

        $this->count = $count;

        return $this;
    }

    public function set_type(string $class) {
        if (!is_subclass_of($class, hierarchy_item::class, true)) {
            throw new \Exception("'{$class}' must be a subclass of '" . hierarchy_item::class, '\'');
        }

        $this->type = $class;

        return $this;
    }

    /**
     * Set hierarchy framework for parent items
     *
     * @param hierarchy_framework|null $framework
     * @return hierarchy
     */
    public function set_framework(?hierarchy_framework $framework) {
        $this->frameworks[] = $framework;

        return $this;
    }

    public function get_next_framework(): ?hierarchy_framework {
        if (empty($this->frameworks)) {
            return null;
        }

        $framework = current($this->frameworks);

        if ($framework === false && key($this->frameworks) === null) {
            reset($this->frameworks);

            $framework = current($this->frameworks);
        }

        next($this->frameworks);

        return $framework;
    }

    /**
     * Create a hierarchy item
     *
     * @param hierarchy_item $parent
     * @return hierarchy_item
     */
    public function create_hierarchy_item(?hierarchy_item $parent): hierarchy_item {
        $item = $this->new_item();

        // If parent is null we need to set framework, if parent is not null we need to set the parent id.
        if ($parent === null) {
            if (empty($framework = $this->get_next_framework())) {
                throw new \Exception('Framework must be set to create root level items');
            }

            $item->set_framework($framework);
        } else {
            $item->set_parent($parent);
        }

        return $item->save_and_return();
    }

    /**
     * Create hierarchy
     *
     * @return bool
     */
    public function create_hierarchy(): bool {
        $depth = $this->variable_depth ? rand(1, $this->depth) : $this->depth;

        $items = [];

        $parents = null;
        $c = 1;

        do {
            $count = $this->variable_count ? rand(1, $this->count) : $this->count;
            $parents = $this->create_hierarchy_level($count, $parents);
            $items = array_merge($items, $parents);
            $c++;
        } while ($c <= $depth);

        $this->items = $items;

        return true;
    }

    /**
     * Create a level of hierarchy items
     *
     * @param int $count
     * @param item[]|null[] $parents
     * @return item[]
     */
    protected function create_hierarchy_level(int $count, ?array $parents = null): array {
        if (empty($parents)) {
            return $this->create_hierarchy_children($count, null);
        }

        $items = [];

        foreach ($parents as $parent) {
            $children = $this->variable_count ? rand(1, $this->count) : $this->count;
            $items = array_merge($items, $this->create_hierarchy_children($children, $parent));
        }

        return $items;
    }

    /**
     * @param int $count
     * @param hierarchy_item|null $parent
     * @return array
     */
    protected function create_hierarchy_children(int $count, ?hierarchy_item $parent): array {
        $items = [];

        for ($c = 1; $c <= $count; $c++) {
            $items[] = $this->create_hierarchy_item($parent);
        }

        return $items;
    }

    /**
     * New up a hierarchy item class
     *
     * @return hierarchy_item
     */
    protected function new_item(): hierarchy_item {
        if ($this->type === null) {
            throw new \Exception('You must set the type first.');
        }

        return new $this->type;
    }
}