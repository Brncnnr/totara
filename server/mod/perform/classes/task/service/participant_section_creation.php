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

use coding_exception;
use core\collection;
use core\orm\query\builder;
use mod_perform\entity\activity\participant_section;
use mod_perform\state\participant_section\availability_not_applicable;
use mod_perform\state\participant_section\open;
use mod_perform\state\participant_section\progress_not_applicable;
use mod_perform\state\participant_section\not_started;
use mod_perform\task\service\data\subject_instance_activity_collection;
use stdClass;

/**
 * Class participant_section_creation
 *
 * @package mod_perform\task\service
 */
class participant_section_creation {

    /**
     * @var array
     */
    private $participant_sections = [];

    /**
     * Maximum number of participant sections aggregated before bulk insert.
     *
     * @var int
     */
    private $buffer_count = BATCH_INSERT_MAX_ROW_COUNT;

    /**
     * Generate participant sections for a list of participant instances.
     *
     * @param collection|participant_instance_dto[] $participant_instances
     * @param subject_instance_activity_collection $activity_collection
     * @return void
     */
    public function generate_sections(collection $participant_instances, subject_instance_activity_collection $activity_collection): void {
        builder::get_db()->transaction(
            function () use ($participant_instances, $activity_collection) {
                $activity_ids = array_unique($participant_instances->pluck('activity_id'));
                $activity_collection->load_activity_configs_if_missing($activity_ids);

                foreach ($participant_instances as $participant_instance) {
                    $participant_section_relationships = $activity_collection
                        ->get_activity_config($participant_instance->activity_id)
                        ->get_section_relationships_owned_by_relationship_id($participant_instance->core_relationship_id);

                    foreach ($participant_section_relationships as $section_relationship) {
                        if ((int)$section_relationship->can_answer === 1) {
                            $progress = not_started::get_code();
                            $availability = open::get_code();
                        } else if ((int)$section_relationship->can_view === 1) {
                            $progress = progress_not_applicable::get_code();
                            $availability = availability_not_applicable::get_code();
                        } else {
                            throw new coding_exception(
                                'Tried to create participant section for relationship which cannot view or answer'
                            );
                        }

                        $data = new stdClass();
                        $data->section_id = $section_relationship->section_id;
                        $data->progress = $progress;
                        $data->availability = $availability;
                        $data->created_at = time();
                        $data->participant_instance_id = $participant_instance->id;

                        $this->aggregate_participant_section($data);
                    }
                }
                $this->save_participant_sections();
            }
        );
    }

    /**
     * Aggregates the participant_sections to create.
     *
     * @param stdClass $data
     * @return void
     */
    private function aggregate_participant_section(stdClass $data): void {
        $this->participant_sections[] = $data;

        if (count($this->participant_sections) === $this->buffer_count) {
            $this->save_participant_sections();
        }
    }

    /**
     * Inserts records into the database.
     *
     * @return void
     */
    private function save_participant_sections(): void {
        if (empty($this->participant_sections)) {
            return;
        }

        builder::get_db()->insert_records(
            participant_section::TABLE,
            $this->participant_sections
        );
        $this->participant_sections = [];
    }
}
