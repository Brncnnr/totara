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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace aggregation_test_aggregation;

use totara_competency\overall_aggregation;
use totara_hierarchy\entity\scale_value;

defined('MOODLE_INTERNAL') || die();

/**
 * To use this in tests, call \totara_competency\testing\generator::create_test_aggregation() instead of instantiating it directly.
 */
class test_aggregation extends overall_aggregation {

    /**
     * @var array|scale_value[]
     */
    private $test_achieved_values = [];
    private $test_achieved_vias = [];

    /**
     * @param $achieved_values
     * @param $achieved_vias
     * @return test_aggregation
     */
    public function set_test_aggregated_data($achieved_values, $achieved_vias): self {
        $this->test_achieved_values = $achieved_values;
        $this->test_achieved_vias = $achieved_vias;
        return $this;
    }

    /**
     * Test aggregation.
     *
     * @param int $user_id
     * @return void
     */
    protected function do_aggregation(int $user_id): void {
        $this->set_user_achievement($user_id,
            $this->test_achieved_vias[$user_id] ?? [],
            $this->test_achieved_values[$user_id] ?? null
        );
    }

}
