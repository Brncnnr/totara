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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\hook;

use totara_core\hook\base;

/**
 * Hook to be triggered when the validity of a criterion changes
 */
class criteria_validity_changed extends base {

    /** @var array */
    protected $criteria_ids;

    /**
     * @param array $criteria_ids List of affected criteria ids
     */
    public function __construct(array $criteria_ids) {
        $this->criteria_ids = $criteria_ids;
    }

    /**
     * Return a list of affected criteria ids
     * @return array
     */
    public function get_criteria_ids(): array {
        return $this->criteria_ids;
    }
}
