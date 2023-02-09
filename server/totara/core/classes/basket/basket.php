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
 * @package totara_core
 */

namespace totara_core\basket;

use totara_core\basket\storage\simple_adapter;
use totara_core\basket\storage\adapter;

defined('MOODLE_INTERNAL') || die();

/**
 * DEPRECATED
 *
 * The basket is essential a data holder storing a number of items for later use.
 * This basic implementation stores the data within the class, it is not persistant
 * outside of the instance
 *
 * @deprecated since Totara 13
 */
class basket implements basket_interface {

    /**
     * Unique name for this basket
     *
     * @var string
     */
    protected $key;

    /**
     * @var adapter
     */
    private $storage;

    /**
     * @param string $key name/id of the basket
     * @param adapter|null $storage defaults to simple_storage_adapter
     */
    public function __construct(string $key, adapter $storage = null) {
        $this->key = $key;
        $this->storage = $storage ?? new simple_adapter();
    }

    /**
     * @deprecated since Totara 13
     */
    public function replace(array $items): basket_interface {
        $this->delete();
        $this->add($items);
        return $this;
    }

    /**
     * @deprecated since Totara 13
     */
    public function add(array $items): basket_interface {
        $stored_items = $this->load();
        $items = array_merge($stored_items, $items);
        // make items unique and reset keys
        $items = array_values(array_unique($items));
        sort($items);
        $this->storage->save($this->key, $items);
        return $this;
    }

    /**
     * @deprecated since Totara 13
     */
    public function remove(array $items): basket_interface {
        $items = array_values(
            array_filter(
                $this->load(),
                function ($value) use ($items) {
                    return !in_array($value, $items);
                }
            )
        );
        if (empty($items)) {
            $this->storage->delete($this->key);
        } else {
            $this->storage->save($this->key, $items);
        }
        return $this;
    }

    /**
     * @deprecated since Totara 13
     */
    public function delete(): basket_interface {
        $this->storage->delete($this->key);
        return $this;
    }

    /**
     * @deprecated since Totara 13
     */
    public function load(): array {
        return $this->storage->load($this->key) ?? [];
    }

    /**
     * @deprecated since Totara 13
     */
    public function get_key(): string {
        return $this->key;
    }

}