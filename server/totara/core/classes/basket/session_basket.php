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

use totara_core\basket\storage\session_adapter;

defined('MOODLE_INTERNAL') || die();

/**
 * DEPRECATED
 *
 * Decorator for the basket with session storage and limiter
 *
 * @deprecated since Totara 13
 */
class session_basket implements basket_interface {

    /**
     * @var basket_interface
     */
    private $basket;

    /**
     * @var basket_limiter
     */
    private $limiter;

    /**
     * initiate the decorator
     *
     * @param string $key
     */
    public function __construct(string $key) {
        $storage = new session_adapter('baskets');
        $this->basket = new basket($key, $storage);
        $this->limiter = new basket_limiter($this->basket, $this->get_configured_limit());
    }

    /**
     * Get limit set in config or null if no limit is set in config
     *
     * @return int|null
     */
    private function get_configured_limit(): ?int {
        global $CFG;

        $limit = null;
        if (isset($CFG->basket_item_limit) && $CFG->basket_item_limit >= 0) {
            $limit = $CFG->basket_item_limit;
        }
        return $limit;
    }

    /**
     * replace current items in the basket with the given items, items can be ids for example, but not limited to
     *
     * @param array $items
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function replace(array $items): basket_interface {
        // if we don't delete the items before validation
        // it will fail as validation always uses the current state
        $this->delete()->validate($items);

        $this->basket->replace($items);
        return $this;
    }

    /**
     * add given items to the basket, items can be ids for example, but not limited to
     * If an item already exists in the basket it is overwritten as entries are unique
     *
     * @param array $items
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function add(array $items): basket_interface {
        $this->validate($items);
        $this->basket->add($items);
        return $this;
    }

    /**
     * Validate our items against our given limit
     *
     * @param $items
     * @throws \moodle_exception
     */
    private function validate($items) {
        if ($this->limiter->is_limit_reached($items)) {
            throw new basket_limit_exception('basket_error_limit', 'totara_core', '', $this->limiter->get_limit());
        }
    }

    /**
     * Get the limit for this basket
     *
     * @return int
     *
     * @deprecated since Totara 13
     */
    public function get_limit(): int {
        return $this->limiter->get_limit();
    }

    /**
     * remove given items from the basket, items can be ids for example, but not limited to
     *
     * @param array $items
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function remove(array $items): basket_interface {
        $this->basket->remove($items);
        return $this;
    }

    /**
     * this removes the basket completely, essentially clearing it
     *
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function delete(): basket_interface {
        $this->basket->delete();
        return $this;
    }

    /**
     * returns all items contained in the basket
     *
     * @return array
     *
     * @deprecated since Totara 13
     */
    public function load(): array {
        return $this->basket->load();
    }

    /**
     * Factory method to inline fetching content from a basket
     *
     * @param string $basket_key id of the basket to load
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function fetch($basket_key): array {
        return (new static($basket_key))->load();
    }

    /**
     * @return string
     *
     * @deprecated since Totara 13
     */
    public function get_key(): string {
        return $this->basket->get_key();
    }

}