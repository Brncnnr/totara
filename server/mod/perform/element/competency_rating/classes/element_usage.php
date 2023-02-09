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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace performelement_competency_rating;

use mod_perform\models\activity\helpers\element_usage as base_element_usage;
use totara_core\advanced_feature;

/**
 * Configuration of where the competency rating element can be used.
 *
 * @package performelement_competency_rating
 */
class element_usage extends base_element_usage {

    /**
     * @inheritDoc
     */
    public function get_can_be_top_level_element(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function is_compatible_child_element(string $plugin_name, ?string $data): bool {
        if ($plugin_name !== 'linked_review' || empty($data) || !$this->feature_available()) {
            return false;
        }
        $element_data = json_decode($data, true);

        return !empty($element_data['content_type']) && $element_data['content_type'] === 'totara_competency';
    }

    /**
     * Checks if the element plugin is available for use.
     *
     * @return bool
     */
    private function feature_available(): bool {
        return advanced_feature::is_enabled('competency_assignment') && advanced_feature::is_enabled('competencies');
    }
}
