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


/**
 * Class criterion
 *
 * @method \totara_competency\pathway get_data(?string $property = null)
 *
 * @package degeneration\items\pathways
 */
class learning_plan extends pathway {

    /**
     * Get list of properties to be added to the generated item
     *
     * @return array
     */
    public function get_properties(): array {

        // I know values doesn't make sense here, it's just for improved readability
        return array_values([
            'competency' => $this->get_competency()->get_data(),
            'sort_order' => null,
        ]);
    }

    /**
     * Get type
     * Create learning plan pathway function is named differently in the generator
     *
     * @return string
     */
    public function get_type(): string {
        return 'learning_plan_pathway';
    }
}