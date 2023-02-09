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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\local\export\loader;

/**
 * For loading the content within the system
 */
abstract class content_loader {
    /**
     * Preventing this class's children from having complicated constructor.
     * data_loader constructor
     */
    final public function __construct() {
    }

    /**
     * Returning all the ids
     * @return int[]
     */
    abstract public function get_all_ids(): array;

    /**
     * Returning the count number of ids.
     *
     * @return int
     */
    abstract public function get_total(): int;

    /**
     * @return string
     */
    abstract public function get_content_type(): string;
}
