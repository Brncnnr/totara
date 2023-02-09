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

namespace pathway_perform_rating\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\participant_instance;
use pathway_perform_rating\models\perform_rating;

/**
 * Mutation to rate a competency in a review question in a performance activity
 */
class linked_competencies_rate extends mutation_resolver {

    /**
     * Rate the competency
     *
     * @param array $args
     *
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $participant_instance_id = $input['participant_instance_id'];
        $competency_id = $input['competency_id'];
        $scale_value_id = $input['scale_value_id'];
        $section_element_id = $input['section_element_id'];

        $participant_instance = participant_instance::load_by_id($participant_instance_id);
        $existing_rating = perform_rating::get_existing_rating(
            $competency_id, $participant_instance->subject_instance_id, $participant_instance->core_relationship_id
        );

        if ($existing_rating) {
            return [
                'rating' => $existing_rating,
                'already_exists' => true,
            ];
        }

        $new_rating = perform_rating::create($competency_id, $scale_value_id, $participant_instance_id, $section_element_id);

        return ['rating' => $new_rating];
    }


    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competency_assignment'),
            new require_advanced_feature('performance_activities'),
        ];
    }

}
