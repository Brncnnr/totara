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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 */

namespace core\orm\entity\traits;


trait has_visible_filter {

    /**
     * Select only visible items
     *
     * @param bool $visible A flag whether the item is visible
     * @return $this
     */
    public function filter_by_visible(?bool $visible = true) {
        // Ignore the filter if null value is supplied
        if (!is_null($visible)) {
            $this->where('visible', $visible);
        }
        return $this;
    }

    /**
     * Select only hidden items
     *
     * @return $this
     */
    public function filter_by_hidden() {
        return $this->filter_by_visible(false);
    }
}