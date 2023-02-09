<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform\task\service;

use core\collection;
use mod_perform\entity\activity\section_relationship;
use mod_perform\task\service\data\subject_instance_activity_collection;

class participant_instance_creation extends participant_instance_service {

    /**
     * Generates participant instances for a collection of subject instances.
     *
     * @param collection|subject_instance_dto[] $subject_instance_dtos
     * @param subject_instance_activity_collection|null $subject_instance_activity_collection
     *
     * @return void
     */
    public function generate_instances(collection $subject_instance_dtos, subject_instance_activity_collection $subject_instance_activity_collection = null): void {
        $this->activity_collection = $subject_instance_activity_collection ?? new subject_instance_activity_collection();

        $this->aggregate_participant_instances($subject_instance_dtos);
        $this->save_data();
    }

    /**
     * Create participant instances for a list of relationships.
     *
     * @param array $relationship_data Contains core_relationships, activity_id, subject instance and participant ids.
     * @return void
     */
    protected function create_participant_instances_for_relationships(array $relationship_data): void {
        $section_relationships_per_core_relationship = $relationship_data['section_relationships_per_core_relationship'];
        $subject_instance = $relationship_data['subject_instance'];
        $participant_dtos = $relationship_data['participant_dtos'];

        /**
         * @var int $core_relationship_id
         * @var section_relationship[] $section_relationships
         */
        foreach ($section_relationships_per_core_relationship as $core_relationship_id => $section_relationships) {
            $relationship_participants = $participant_dtos[$core_relationship_id] ?? null;

            if (!empty($relationship_participants)) {
                $this->create_participant_instances_for_user_list(
                    $this->build_participant_instance_data(
                        $core_relationship_id,
                        $subject_instance,
                        $section_relationships
                    ),
                    $relationship_participants
                );
            }
        }
    }
}