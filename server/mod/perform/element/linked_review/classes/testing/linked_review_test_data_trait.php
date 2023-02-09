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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\testing;

use mod_perform\models\activity\element as element_model;
use mod_perform\testing\generator as perform_generator;

/**
 * @group perform
 */
trait linked_review_test_data_trait {
    /**
     * Create activity with elements
     *
     * @param array $users
     * @param string $subject_user
     * @param array $activity_params
     */
    private function create_activity_with_elements(array $users, string $subject_user, array $activity_params): void {
        $perform_generator = perform_generator::instance();

        $activity = $perform_generator->create_activity_in_container(['activity_name' => $activity_params['name'], 'create_section' => false]);
        $section = $perform_generator->create_section($activity);

        $section_relationships = [];

        $relationships = $activity_params['relationships'];
        $elements = $activity_params['elements'];

        foreach ($relationships as $relationship) {
            $section_relationships[$relationship] = $perform_generator->create_section_relationship(
                $section,
                ['relationship' => $relationship]
            );
        }

        foreach ($elements as $element_params) {
            $element_plugin = $element_params['plugin_type'];
            $element_data = null;

            if ($element_plugin == 'linked_review') {
                $content_type = $element_params['content_type'] ?? 'company_goal';
                $content_type_settings = [];

                switch ($content_type) {
                    case 'personal_goal':
                    case 'company_goal':
                        $content_type_settings = [
                            'enable_status_change' => false,
                        ];
                        break;

                    case 'totara_competency':
                        $content_type_settings = [
                            'enable_rating' => false,
                        ];
                        break;
                }
                $element_data = json_encode([
                    'content_type' => $content_type,
                    'content_type_settings' => $content_type_settings,
                    'selection_relationships' => [$perform_generator->get_core_relationship($element_params['selection_relationship'])->id],
                ]);
            }

            $element = element_model::create($activity->get_context(), $element_plugin, $element_plugin . '_title', '', $element_data);
            $section_element = $perform_generator->create_section_element($section, $element);

            $subject_instance = $perform_generator->create_subject_instance([
                'activity_id' => $activity->id,
                'subject_user_id' => $users[$subject_user]->id,
                'relationships_can_answer' => implode(',', $relationships),
                'include_questions' => false,
            ]);

            foreach ($relationships as $relationship) {
                $participant_section = $perform_generator->create_participant_instance_and_section(
                    $activity,
                    $users[$relationship],
                    $subject_instance->id,
                    $section,
                    $section_relationships[$relationship]->core_relationship->id
                );
            }
        }
    }

}
