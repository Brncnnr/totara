<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_cohort
 */

namespace totara_cohort\hook;

/**
 * Hook to allow plugins to list areas that will be impacted by the deletion of an audience.
 */
class delete_affects extends \totara_core\hook\base {
    /**
     * @var array
     */
    private $affected = [];

    /**
     * @var int
     */
    private $id;

    /**
     * @param int $id
     */
    public function __construct(int $id) {
        $this->id = $id;
    }

    /**
     * Get the ID of the audience.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Add details about an affected area to be displayed.
     *
     * @param string $component Frankenstyle component name. Not displayed.
     * @param string $area Area to display in UI, e.g. "Scheduled actions"
     * @param string $changes Description of affect
     * @param int $count Number of items affected
     * @return void
     */
    public function add_affected(string $component, string $area, string $changes, int $count): void {
        $this->affected[] = (object)compact('component', 'area', 'changes', 'count');
    }

    /**
     * Get array of affected areas.
     *
     * @return array Array of objects with component, area, changes, and count fields. {@see add_affected()}
     */
    public function get_affected(): array {
        return $this->affected;
    }
}
