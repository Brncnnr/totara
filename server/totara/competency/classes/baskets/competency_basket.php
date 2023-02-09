<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\baskets;

use core\orm\entity\filter\basket;
use core\orm\entity\filter\hierarchy_item_visible;
use totara_core\basket\basket_interface;
use totara_core\basket\session_basket;
use totara_hierarchy\entity\competency;

/**
 * Decorator around session_basket with additional competency specific functionality
 */
class competency_basket implements basket_interface {

    /**
     * @var session_basket
     */
    private $basket;

    public function __construct(string $basket_key) {
        $this->basket = new session_basket($basket_key);
    }

    /**
     * Syncs the items in the basket checking if the competencies exist or is visible.
     * Returns all items which are in the basket but do not exist in the database or are hidden.
     * Also removes the items from the basket which do not exist anymore
     *
     * @return array|competency[] returns diff, empty if all competencies are valid
     */
    public function sync(): array {
        $expected_ids = $this->load();

        $competencies = competency::repository()
            ->set_filter((new basket())->set_value($this))
            ->set_filter((new hierarchy_item_visible())->set_value(true))
            ->get();

        $current_ids = $competencies->pluck('id');

        sort($expected_ids);
        sort($current_ids);
        $diff = array_diff($expected_ids, $current_ids);

        if (!empty($diff)) {
            // Remove them from the basket as they are out of sync
            $this->remove($diff);
        }
        return $diff;
    }

    /**
     * replace current items in the basket with the given items, items can be ids for example, but not limited to
     *
     * @param array $items
     * @return basket_interface
     */
    public function replace(array $items): basket_interface {
        $this->basket->replace($items);
        return $this;
    }

    /**
     * add given items to the basket, items can be ids for example, but not limited to
     * If an item already exists in the basket it is overwritten as entries are unique
     *
     * @param array $items
     * @return basket_interface
     */
    public function add(array $items): basket_interface {
        $this->basket->add($items);
        return $this;
    }

    /**
     * remove given items from the basket, items can be ids for example, but not limited to
     *
     * @param array $items
     * @return basket_interface
     */
    public function remove(array $items): basket_interface {
        $this->basket->remove($items);
        return $this;
    }

    /**
     * this removes the basket completely, essentially clearing it
     *
     * @return basket_interface
     */
    public function delete(): basket_interface {
        $this->basket->delete();
        return $this;
    }

    /**
     * returns all items contained in the basket
     *
     * @return array
     */
    public function load(): array {
        return $this->basket->load();
    }

    /**
     * @return string
     */
    public function get_key(): string {
        return $this->basket->get_key();
    }
}