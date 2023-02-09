<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\entity;

trait has_active_trait {
    /**
     * @return bool
     * @internal called by the entity class
     */
    public function get_active_attribute(): bool {
        return $this->get_attributes_raw()['active'] ?? true;
    }

    /**
     * @param bool $value
     * @return bool
     * @internal called by the entity class
     */
    public function set_active_attribute(bool $value): bool {
        return (bool)$this->set_attribute_raw('active', $value);
    }
}