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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating;

use context_system;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use pathway_perform_rating\models\perform_rating as perform_rating_model;
use totara_competency\base_achievement_detail;

class achievement_detail extends base_achievement_detail {

    /**
     * @inheritDoc
     */
    public function get_achieved_via_strings(): array {
        if (empty($this->related_info)) {
            return [];
        }

        $rating = perform_rating_model::load_by_id($this->related_info['rating_id']);
        $rater = $rating->rater_user ? fullname($rating->rater_user->to_record()) : get_string('rater_details_removed', 'pathway_perform_rating');
        $activity = $rating->get_activity();
        if ($activity) {
            $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());
            $string = get_string('activity_log_rating_by', 'pathway_perform_rating', [
                'name' => $rater,
                'relationship' => $rating->get_rater_relationship()->name,
                'activity' => $formatter->format($activity->name),
            ]);
        } else {
            // The activity got removed.
            $string = get_string('activity_log_activity_removed', 'pathway_perform_rating', [
                'name' => $rater,
                'relationship' => $rating->get_rater_relationship()->name,
            ]);
        }

        return [$string];
    }

    /**
     * If a perform rating pathway value has been achieved, the corresponding rating record should be added here.
     * This will store the appropriate data to be used when processing the information on how a value was achieved.
     *
     * @param perform_rating_model|null $rating
     */
    public function add_rating(?perform_rating_model $rating) {
        if (!is_null($rating)) {
            $this->related_info['rating_id'] = $rating->id;
            $this->set_scale_value_id($rating->scale_value_id);
        }
    }
}