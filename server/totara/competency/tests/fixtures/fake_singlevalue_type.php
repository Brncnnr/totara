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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package test
 */

namespace pathway_fake_singlevalue_type;

use totara_competency\base_achievement_detail;
use totara_competency\pathway;

/**
 * This pathway is just used to test additional non-standard pathway types
 */
class fake_singlevalue_type extends pathway {

    public const CLASSIFICATION = self::PATHWAY_SINGLE_VALUE;

    /**
     * Load the data specific to the type of pathway.
     */
    protected function fetch_configuration(): void {
        // Nothing to do.
    }

    /**
     * Has the pathway specific configuration changed?
     *
     * @return bool
     */
    protected function configuration_is_dirty(): bool {
        return false;
    }

    /**
     * Save the pathway specific configuration
     */
    protected function save_configuration() {
        // Nothing to do.
    }

    /**
     * Delete the pathway specific detail
     */
    protected function delete_configuration(): void {
        // Nothing to do.
    }

    /**
     * Calculates what value the user has achieved for this pathway.
     *
     * The value is included in the pathway's implementation of base_achievement_detail. This allows
     * us to get the scale value id from that instance and also information related to how the
     * value was achieved.
     *
     * @param int $user_id
     * @return base_achievement_detail as implemented by the pathway plugin in question
     */
    public function aggregate_current_value(int $user_id): base_achievement_detail {
        return new class extends base_achievement_detail {
            public function get_achieved_via_strings(): array {
                return [];
            }
        };
    }
}