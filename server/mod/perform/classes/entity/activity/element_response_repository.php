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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_section\complete;
use totara_core\entity\relationship as relationship_entity;

class element_response_repository extends repository {
    /**
     * Filter responses by a specific (internal) participant id.
     *
     * Can only be used after filter_for_export() as that provides the required joins.
     *
     * @param int $participant_id
     * @return $this
     */
    public function filter_by_participant_for_export(int $participant_id): self {
        return $this->where('perform_participant_instance.participant_source', participant_source::INTERNAL)
            ->where('perform_participant_instance.participant_id', $participant_id);
    }

    /**
     * Filter responses by a specific subject user id.
     *
     * Can only be used after filter_for_export() as that provides the required joins.
     *
     * @param int $subject_user_id
     * @return $this
     */
    public function filter_by_subject_for_export(int $subject_user_id): self {
        return $this->where('perform_subject_instance.subject_user_id', $subject_user_id);
    }

    /**
     * Filter responses to only responses the subject can view.
     *
     * This includes their own responses as well as responses by others in sections where the subject is a participant
     * in that section, and they have can_view in that section.
     *
     * Can only be used after filter_for_export() as that provides the required joins.
     *
     * @param int $subject_user_id
     * @return $this
     * @throws \coding_exception
     */
    public function filter_by_subject_can_view(int $subject_user_id): self {
        $progress_complete = complete::get_code();

        // Match stuff the subject can see, for whatever reason:
        // (
        // - The subject CAN view
        //   AND
        // - Not a draft
        // )
        // OR
        // - Belongs to the subject
        return $this
            ->where(function (builder $builder) use ($progress_complete, $subject_user_id) {
                $builder
                    ->where(function (builder $builder) use ($progress_complete) {
                        $builder->where_raw('COALESCE(subject_relationship.can_view, 0) = 1')
                            ->where('perform_participant_section.progress', $progress_complete);
                    })
                    ->or_where(function (builder $builder) use ($subject_user_id) {
                        $builder->where('perform_participant_instance.participant_id', $subject_user_id)
                                 ->where("perform_participant_instance.participant_source", participant_source::INTERNAL);
                    });
            });
    }

    /**
     * Filter responses to only responses the subject CANNOT view.
     *
     * This excludes their own responses as well as responses by others in sections where the subject is a participant
     * in that section, and they have can_view in that section.
     *
     * Can only be used after filter_for_export() as that provides the required joins.
     *
     * @param int $subject_user_id
     * @return $this
     * @throws \coding_exception
     */
    public function filter_by_subject_cannot_view(int $subject_user_id): self {
        $progress_complete = complete::get_code();

        // Match stuff the subject wouldn't normally see ONLY, except other people's drafts which are still excluded.
        // - Not the subject's
        //   AND
        // - Not a draft
        //   AND
        // - Subject can't view
        return $this->where(function (builder $builder) use ($subject_user_id, $progress_complete) {
            $builder
                ->where('perform_participant_instance.participant_id', '!=', $subject_user_id)
                ->where('perform_participant_section.progress', $progress_complete)
                ->where_raw('COALESCE(subject_relationship.can_view, 0) = 0');
        });
    }

    /**
     * Filter to responses in activities within a specific context (system, category or course)
     *
     * @param \context $context
     * @return $this
     */
    public function filter_by_context(\context $context): self {
        // No need for restrictions for system context.
        if (get_class($context) == 'context_system') {
            return $this;
        }
        return $this->where(function (builder $builder) use ($context) {
            $builder->where('context.id', $context->id)
            ->or_where_like_starts_with('context.path', "{$context->path}/");
        });
    }

    /**
     * Build the common part of the query to fetch response records and associated data.
     *
     * @return $this
     */
    public function filter_for_export(): self {
        $subject_relationship_id = relationship_entity::repository()
            ->select('id')
            ->where('idnumber', '=', 'subject')->one()->id;

        return $this
            ->join('perform_participant_instance', 'participant_instance_id', '=', 'id')
            ->join('perform_subject_instance', 'perform_participant_instance.subject_instance_id', '=', 'id')
            ->join('perform_section_element', 'section_element_id', '=', 'id')
            ->join('perform_section', 'perform_section_element.section_id', '=', 'id')
            ->join('perform_participant_section', function (builder $joining) {
                $joining->where_field('perform_participant_section.section_id', 'perform_section.id')
                    ->where_field('perform_participant_section.participant_instance_id', 'perform_participant_instance.id');
            })
            ->join(['perform_section_relationship', 'subject_relationship'], function (builder $joining) use ($subject_relationship_id) {
                $joining->where_field('perform_section.id', 'subject_relationship.section_id')
                    ->where('subject_relationship.core_relationship_id', '=', $subject_relationship_id);
            })
            ->join('perform_element', 'perform_section_element.element_id', '=', 'id')
            ->join('perform', 'perform_section.activity_id', '=', 'id')
            ->join('course', 'perform.course', '=', 'id')
            ->join('context', function (builder $joining) {
                $joining->where_field('course.id', 'context.instanceid')
                    ->where('context.contextlevel', '=', CONTEXT_COURSE);
            })
            ->select([
                '*', // Everything from perform_element_response table
                'perform.id AS activity_id',
                'perform.name',
                'perform.anonymous_responses',
                'perform_section.id AS section_id',
                'perform_section.title AS section_title',
                'perform_element.id AS element_id',
                'perform_element.title AS element_title',
                'perform_element.plugin_name AS element_type',
                'perform_element.data AS element_data',
                'perform_subject_instance.subject_user_id AS subject_user_id',
                'perform_participant_instance.participant_source',
                'perform_participant_instance.participant_id',
            ])
            ->add_select_raw('COALESCE(subject_relationship.can_view, 0) AS subject_can_view')
            ->add_select_raw('COALESCE(subject_relationship.can_answer, 0) AS subject_can_answer');
    }

    public function find_for_participants_and_section_elements(
        array $participant_instance_ids,
        array $section_element_ids
    ): collection {
        return $this->where_in('section_element_id', $section_element_ids)
            ->where_in('participant_instance_id', $participant_instance_ids)
            ->get();
    }
}