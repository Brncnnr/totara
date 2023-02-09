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
 * @package performelement_redisplay
 */

namespace performelement_redisplay\data_provider;

use core\collection;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\subject_instance as subject_instance_model;

/**
 * Class source_data. Generates source_data used to get the previous responses.
 *
 */
class source_data {

    /**
     * Current subject instance.
     *
     * @param subject_instance_model $current_subject_instance
     */
    private $current_subject_instance;

    public function __construct(subject_instance_model $current_subject_instance) {
        $this->current_subject_instance = $current_subject_instance;
    }

    /**
     * @param int $section_element_id
     * @return array
     */
    public function get_data(int $section_element_id): array {
        $data = [];
        $data['section_element'] = section_element_model::load_by_id($section_element_id);
        $data['activity'] = $data['section_element']->section->activity;
        $data['responding_section_relationships'] = $data['section_element']->section->get_answering_section_relationships();

        /** @var collection $source_subject_instances */
        $source_subject_instances = subject_instance::repository()->get_user_subject_instances_for_activity_before_date(
            $this->current_subject_instance->subject_user_id,
            $data['activity']->id,
            $this->current_subject_instance->created_at
        );
        $data['subject_instance'] = null;
        $data['participant_instances'] = new collection();

        if ($source_subject_instances->count() > 0) {
            $data['subject_instance'] = $this->select_subject_instance($source_subject_instances);
            $data['participant_instances'] = $data['subject_instance']->get_participant_instances_with_relationships(
                $data['responding_section_relationships']->pluck('core_relationship_id')
            );
        }
        return $data;
    }

    /**
     * Select a source subject instance based on the current subject instance data.
     *
     * @param collection $source_subject_instances
     * @return subject_instance_model|null
     */
    private function select_subject_instance(collection $source_subject_instances): ?subject_instance_model {
        $subject_instance_with_same_job_assignment = null;

        if ($this->current_subject_instance->job_assignment_id) {
            $subject_instance_with_same_job_assignment = $source_subject_instances->find(function ($subject_instance) {
                return $subject_instance->job_assignment_id === $this->current_subject_instance->job_assignment_id;
            });
        }
        $source_subject_instance = $subject_instance_with_same_job_assignment ?? $source_subject_instances->first();

        return subject_instance_model::load_by_entity($source_subject_instance);
    }
}