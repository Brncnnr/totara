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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class representing a condition that can be applied to state transitions.
 */
abstract class condition {
    /**
     * @var object instance to be tested for condition
     */
    protected $object = null;

    /**
     * Condition constructor.
     * @param object $object
     */
    public function __construct(object $object) {
        $this->object = $object;
    }

    /**
     * Is condition passing
     * @return bool
     */
    abstract public function pass(): bool;
}
