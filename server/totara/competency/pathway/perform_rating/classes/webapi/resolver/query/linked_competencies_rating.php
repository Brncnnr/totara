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

namespace pathway_perform_rating\webapi\resolver\query;

use context_user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_competency\helpers\capability_helper;
use pathway_perform_rating\models\perform_rating as perform_rating_model;

/**
 * Query to get the latest competency rating for a user given in a performance activity
 */
class linked_competencies_rating extends query_resolver {

    /**
     * Returns the rating
     *
     * @param array $args
     *
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        global $OUTPUT;
        $input = $args['input'];

        $user_id = $input['user_id'];
        $competency_id = $input['competency_id'];

        $ec->set_relevant_context(context_user::instance($user_id));
        capability_helper::require_can_view_profile($user_id);

        $rating = perform_rating_model::get_latest($competency_id, $user_id);

        return [
            'rating' => $rating,
            'default_profile_image' => (string) $OUTPUT->image_url('u/f1'),
        ];
    }


    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competency_assignment'),
            new require_advanced_feature('performance_activities'),
        ];
    }

}
