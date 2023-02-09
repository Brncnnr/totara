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
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;

class linked_review_content_repository extends repository {
    /**
     * Returns the no of records matching the specified content type and ids.
     *
     * @param string content_type content type to look up.
     * @param int[] content_ids list of content ids to further filter the result
     *
     * @return int the matching record count.
     */
    public function get_content_count_for_type(
        string $content_type,
        array $content_ids = []
    ): int {
        if (!empty($content_ids)) {
             $this->where('content_id', $content_ids);
        }

        return $this
            ->where('content_type', $content_type)
            ->count();
    }

    /**
     * Filter linked review content by a subject instance.
     *
     * @param subject_instance $subject subject by which to filter.
     *
     * @return self this object.
     */
    public function filter_by_subject_instance(subject_instance $subject): self {
        return $this->where('subject_instance_id', $subject->id);
    }

    /**
     * Filter linked review responses by a context.
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
            ->as(linked_review_content::TABLE)
            ->add_join($subject_instance, "${linked_review}.subject_instance_id", 'id')
            ->add_join($track_user_assignment, "${subject_instance}.track_user_assignment_id", 'id')
            ->add_join($track, "${track_user_assignment}.track_id", 'id')
            ->add_join(activity::TABLE, "${track}.activity_id", 'id');
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