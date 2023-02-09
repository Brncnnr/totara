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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\hook;

use totara_core\hook\base;
use totara_hierarchy\entity\competency;

class competency_achievement_updated_bulk extends base {

    /**
     * @var array
     */
    protected $user_ids = [];

    /**
     * @var array
     */
    protected $competency_data;

    public function __construct(competency $competency_data) {
        $this->competency_data = [
            'id' => $competency_data->id,
            'fullname' => $competency_data->fullname,
        ];
    }

    /**
     * Add proficiency data for the user id.
     *
     * @param int $user_id
     * @param array $proficiency_data
     * @return void
     */
    public function add_user_id(int $user_id, array $proficiency_data): void {
        $this->user_ids[$user_id] = $proficiency_data;
    }

    /**
     * Get competency id.
     *
     * @return int
     */
    public function get_competency_id(): int {
        return $this->competency_data['id'];
    }

    /**
     * Get competency.
     *
     * @return array
     */
    public function get_competency_data(): array {
        return $this->competency_data;
    }

    /**
     * Get user ids affected.
     *
     * @return array
     */
    public function get_user_ids(): array {
        return array_keys($this->user_ids);
    }

    /**
     * Get proficiency change data of users.
     *
     * @return array where keys are user_ids and value is an array of proficiency data.
     */
    public function get_user_ids_proficiency_data(): array {
        return $this->user_ids;
    }

}