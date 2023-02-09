<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\modal;

use totara_engage\local\helper;
use totara_tui\output\component;

abstract class modal {
    /**
     * modal constructor.
     */
    final public function __construct() {
        // Preventing the construction to be complicated.
    }

    /**
     * @return string
     */
    public function get_id(): string {
        return helper::get_component_name($this);
    }

    /**
     * @return component
     */
    abstract public function get_vue_component(): component;

    /**
     * @return string
     */
    abstract public function get_label(): string;

    /**
     * @return bool
     */
    public function is_expandable(): bool {
        return false;
    }

    /**
     * @return int
     */
    public function get_order(): int {
        return 1;
    }

    /**
     * @return bool
     */
    abstract public function show_modal(): bool;

    /**
     * @return bool
     */
    abstract public function can_share(): bool;
}