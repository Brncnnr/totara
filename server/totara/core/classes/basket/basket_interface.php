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

defined('MOODLE_INTERNAL') || die();

/**
 * DEPRECATED
 *
 * @deprecated since Totara 13
 */
interface basket_interface {

    /**
     * replace current items in the basket with the given items, items can be ids for example, but not limited to
     *
     * @param array $items
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function replace(array $items): basket_interface;

    /**
     * add given items to the basket, items can be ids for example, but not limited to
     * If an item already exists in the basket it is overwritten as entries are unique
     *
     * @param array $items
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function add(array $items): basket_interface;

    /**
     * remove given items from the basket, items can be ids for example, but not limited to
     *
     * @param array $items
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function remove(array $items): basket_interface;

    /**
     * this removes the basket completely, essentially clearing it
     *
     * @return basket_interface
     *
     * @deprecated since Totara 13
     */
    public function delete(): basket_interface;

    /**
     * returns all items contained in the basket
     *
     * @return array
     *
     * @deprecated since Totara 13
     */
    public function load(): array;

    /**
     * @return string
     *
     * @deprecated since Totara 13
     */
    public function get_key(): string;

}