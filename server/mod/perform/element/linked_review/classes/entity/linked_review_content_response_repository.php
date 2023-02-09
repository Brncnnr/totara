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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\entity;

use coding_exception;
use context;
use context_course;
use context_coursecat;
use context_system;

use core\orm\query\builder;
use core\orm\entity\repository;

use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\section_element;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\participant_source;

class linked_review_content_response_repository extends repository {
    /**
     * Filter linked review responses for a specific subject instance.
     *
     * @param subject_instance $subject subject by which to filter.
     *
     * @return self this object.
     */
    public function filter_by_subject_instance(subject_instance $subject): self {
        return $this
            ->add_linked_review_content_join()
            ->where(
                function (builder $builder) use ($subject): builder {
                    $parent_col = linked_review_content::TABLE . '.subject_instance_id';
                    return $builder->where($parent_col, $subject->id);
                }
            );
    }

    /**
     * Filter linked review responses for a specific subject user id.
     *
     * @param int $user_id subject user id by which to filter.
     *
     * @return self this object.
     */
    public function filter_by_subject_user_id(int $user_id): self {
        $subject_instance = subject_instance::TABLE;
        $linked_review_table = linked_review_content::TABLE;

        return $this
            ->add_linked_review_content_join()
            ->add_join($subject_instance, "${linked_review_table}.subject_instance_id", 'id')
            ->where("${subject_instance}.subject_user_id", $user_id);
    }

    /**
     * Filter linked review responses for a specific participant instance.
     *
     * @param participant_instance $participant participant by which to filter.
     *
     * @return self this object.
     */
    public function filter_by_participant_instance(participant_instance $participant): self {
        $participant_instance = participant_instance::TABLE;

        return $this
            ->as(linked_review_content_response::TABLE)
            ->add_participant_instance_join()
            ->where("${participant_instance}.id", $participant->id);
    }

    /**
     * Filter linked review responses by participant.
     *
     * @param int $user_id participant user id by which to filter.
     *
     * @return self this object.
     */
    public function filter_by_participant_user_id(int $user_id): self {
        $participant_instance = participant_instance::TABLE;

        return $this
            ->as(linked_review_content_response::TABLE)
            ->add_participant_instance_join()
            ->where("${participant_instance}.participant_id", $user_id)
            ->where("${participant_instance}.participant_source", participant_source::INTERNAL);
    }

    /**
     * Filter linked review responses by context.
     *
     * @param context $context context by which to filter.
     *
     * @return self this object.
     */
    public function filter_by_context(context $context): self {
        if ($context instanceof context_system
            || $this->has_join('context')) {
            return $this;
        }

        $this->add_activity_joins();

        $activity_table_col = activity::TABLE . '.course';
        if ($context instanceof context_coursecat) {
            $this->add_join('course', $activity_table_col, 'id');
            $context_instance_field = 'course.category';
        } else if ($context instanceof context_course) {
            $context_instance_field = $activity_table_col;
        } else {
            throw new coding_exception('filter_by_context() does not support filtering by ' . get_class($context));
        }

        return $this->join(
            'context',
            function (builder $joining) use ($context_instance_field) {
                $joining
                    ->where_field($context_instance_field, 'context.instanceid')
                    ->where('context.contextlevel', '=', CONTEXT_COURSE);
            }
        );
    }

    /**
     * Add the joins required to get activity fields.
     *
     * @return self this object.
     */
    public function add_activity_joins(): self {
        $linked_review = linked_review_content::TABLE;
        $subject_instance = subject_instance::TABLE;
        $track_user_assignment = track_user_assignment::TABLE;
        $track = track::TABLE;

        return $this
            ->as(linked_review_content_response::TABLE)
            ->add_linked_review_content_join()
            ->add_participant_instance_join()
            ->add_join($subject_instance, "${linked_review}.subject_instance_id", 'id')
            ->add_join($track_user_assignment, "${subject_instance}.track_user_assignment_id", 'id')
            ->add_join($track, "${track_user_assignment}.track_id", 'id')
            ->add_join(activity::TABLE, "${track}.activity_id", 'id');
    }

    /**
     * Add the join required to get the parent linked review content table.
     *
     * @return self this object.
     */
    private function add_linked_review_content_join(): self {
        $linked_responses = linked_review_content_response::TABLE;

        return $this->add_join(
            linked_review_content::TABLE,
            "${linked_responses}.linked_review_content_id",
            'id'
        );
    }

    /**
     * Add the join required to get the participant instance details.
     *
     * @return self this object.
     */
    public function add_participant_instance_join(): self {
        $linked_responses = linked_review_content_response::TABLE;

        return $this->add_join(
            participant_instance::TABLE,
            "${linked_responses}.participant_instance_id",
            'id'
        );
    }

    /**
     * Add the joins required to get element fields.
     *
     * @return self this object.
     */
    public function add_element_joins(): self {
        $linked_review = linked_review_content::TABLE;
        $section_element = section_element::TABLE;

        return $this
            ->add_linked_review_content_join()
            ->add_join($section_element, "${linked_review}.section_element_id", 'id')
            ->add_join(element::TABLE, "${section_element}.element_id", 'id');
    }

    /**
     * Add the specified join if it does not exist.
     *
     * @param string $to_table target table to which to join.
     * @param string $from_table_col "<table>.<column>" string indicating the
     *        source from which to join.
     * @param string $to_column target column to which to join.
     *
     * @return self this object.
     */
    private function add_join(
        string $to_table,
        string $from_table_col,
        string $to_column
    ): self {
        if (!$this->has_join($to_table)) {
            $this->join($to_table, $from_table_col, $to_column);
        }

        return $this;
    }
}