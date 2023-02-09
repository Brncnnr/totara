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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace degeneration\items\pathways;


use degeneration\items\criteria\criterion;

/**
 * Class criterion
 *
 * @method \totara_competency\pathway get_data(?string $property = null)
 *
 * @package degeneration\items\pathways
 */
class criteria_group extends pathway {

    /**
     * Criteria included in this pathway
     *
     * @var criterion[]
     */
    protected $criteria = [];

    /**
     * Add competency criterion to this pathway
     *
     * @param criterion $criterion
     * @return $this
     */
    public function add_criterion(?criterion $criterion) {
        if (!$criterion) {
            return $this;
        }

        $this->criteria[] = $criterion;

        return $this;
    }

    /**
     * Get criteria
     *
     * @return array
     */
    public function get_criteria(): array {
        return array_map(function (criterion $criterion) {
            return $criterion->get_data();
        }, $this->criteria);
    }

    /**
     * Get list of properties to be added to the generated item
     *
     * @return array
     */
    public function get_properties(): array {

        // I know values doesn't make sense here, it's just for improved readability
        return array_values([
            'competency' => $this->get_competency()->get_data(),
            'criteria' => $this->get_criteria(),
            'scale_value' => $this->scale_value,
            'sort_order' => null,
        ]);
    }
}
