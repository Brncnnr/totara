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

use coding_exception;
use context;
use context_course;
use context_coursecat;
use context_system;
use core\orm\entity\repository;
use core\orm\query\builder;
use mod_perform\models\activity\participant_source;
use totara_core\advanced_feature;

class participant_instance_repository extends repository {
    private const PIR_USER_JOIN = 'pir_user_join';

    /**
     * Should one user be able to see the other users profile details in the context of mod perform
     * based on participant instance records.
     *
     * Will return true if the viewing user share a subject instance with the target user,
     * or if the the target user is the subject of a subject_instance that the viewing user is participating in.
     *
     * @param int $viewing_user_id The user requesting to view the target user
     * @param int $target_user_id The user who's
     * @return bool
     * @see subject_static_instance_repository::user_can_view_other_users_profile
     */
    public static function user_can_view_other_users_profile(int $viewing_user_id, int $target_user_id): bool {
        $shared_subject_instance = builder::table(participant_instance::TABLE)
            ->as('other_pi')
            ->select('id')
            ->where('participant_id', $target_user_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->where_field('subject_instance_id', 'main_pi.subject_instance_id')
            ->where_field('id', '<>', 'main_pi.id');

        $participant_in_subject_about_target = builder::table(subject_instance::TABLE)
            ->as('si2')
            ->where('subject_user_id', $target_user_id)
            ->where_field('id', 'main_pi.subject_instance_id');

        return builder::table(participant_instance::TABLE)
            ->as('main_pi')
            ->join([subject_instance::TABLE, 'si'], 'subject_instance_id', 'id')
            ->join([track_user_assignment::TABLE, 'tua'], 'si.track_user_assignment_id', 'id')
            ->join([track::TABLE, 't'], 'tua.track_id', 'id')
            ->join([activity::TABLE, 'a'], 't.activity_id', 'id')
            ->where('participant_id', $viewing_user_id)
            ->where('participant_source', participant_source::INTERNAL)
            ->where(function (builder $builder) use ($target_user_id) {
                // Not anonymous activity OR the target is the subject of the activity that the viewer is a participant in.
                return $builder->where('a.anonymous_responses', 0)
                    ->or_where('si.subject_user_id', $target_user_id);
            })
            ->where(function (builder $builder) use ($shared_subject_instance, $participant_in_subject_about_target) {
                return $builder->where_exists($shared_subject_instance)
                    ->or_where_exists($participant_in_subject_about_target);
            })
            ->exists();
    }

    /**
     * Filter participant instances by the participant user id.
     *
     * @param int $participant_user_id
     * @return participant_instance_repository
     */
    public function filter_by_participant_user(int $participant_user_id) {
        return $this->where('participant_source', participant_source::INTERNAL)
            ->where('participant_id', $participant_user_id);
    }

    /**
     * Filter participant instances by the specified activity.
     *
     * @param int $activity_id
     * @return $this
     */
    public function filter_by_activity(int $activity_id): self {
        return $this
            ->add_activity_joins()
            ->where(activity::TABLE . '.id', $activity_id);
    }

    /**
     * Filter participant instances by the specified course container for the activity.
     *
     * @param int $course_id
     * @return $this
     */
    public function filter_by_course(int $course_id): self {
        return $this
            ->add_activity_joins()
            ->where(activity::TABLE . '.course', $course_id);
    }

    /**
     * Filter to participant instances at or below a specified context.
     *
     * @param context $context System, Course Category or Course context instance
     * @return $this
     */
    public function filter_by_context(context $context): self {
        // No need for restrictions for system context.
        if ($context instanceof context_system) {
            return $this;
        }

        if ($this->has_join('context')) {
            throw new coding_exception('context join has already been applied to this repository instance.');
        }

        $this->add_activity_joins();

        if ($context instanceof context_coursecat) {
            $this->join('course', activity::TABLE . '.course', 'id');
            $context_instance_field = 'course.category';
        } else if ($context instanceof context_course) {
            $context_instance_field = activity::TABLE . '.course';
        } else {
            throw new coding_exception('filter_by_context() does not support filtering by ' . get_class($context));
        }

        return $this
            ->join('context', function (builder $builder) use ($context, $context_instance_field) {
                $builder
                    ->where_field($context_instance_field, 'context.instanceid')
                    ->where('context.contextlevel', $context->contextlevel);
            })
            ->where(function (builder $builder) use ($context) {
                $builder
                    ->where('context.id', $context->id)
                    ->or_where_like_starts_with('context.path', "{$context->path}/");
            });
    }

    /**
     * Add join to filter for non-deleted (and suspended if applicable) participant users
     *
     * @param builder|repository $repository_or_builder
     * @param string $participant_instance_alias
     */
    public static function add_user_not_hidden_filter($repository_or_builder, string $participant_instance_alias): void {
        static::add_user_not_deleted_filter($repository_or_builder, $participant_instance_alias);

        if (get_config(null, 'perform_hide_suspended_users')) {
            $repository_or_builder->where(
                function (builder $builder) use ($participant_instance_alias) {
                    $builder->or_where($participant_instance_alias.'.participant_source', participant_source::EXTERNAL)
                        ->or_where(self::PIR_USER_JOIN.'.suspended', 0);
                }
            );
        }
    }

    /**
     * Add join to filter for non-deleted participant users
     *
     * @param builder|repository $repository_or_builder
     * @param string $participant_instance_alias
     */
    public static function add_user_not_deleted_filter($repository_or_builder, string $participant_instance_alias): void {
        if (!$repository_or_builder->has_join(self::PIR_USER_JOIN)) {
            $repository_or_builder->left_join(['user', self::PIR_USER_JOIN], function (builder $builder) use ($participant_instance_alias) {
                $builder->where_field('id', $participant_instance_alias.'.participant_id')
                    ->where($participant_instance_alias.'.participant_source', participant_source::INTERNAL);
            })->where(
                function (builder $builder) use ($participant_instance_alias) {
                    $builder->or_where($participant_instance_alias.'.participant_source', participant_source::EXTERNAL)
                        ->or_where(self::PIR_USER_JOIN.'.deleted', 0);
                }
            );
        }
    }

    /**
     * Add the joins required to get activity fields.
     *
     * @return $this
     */
    private function add_activity_joins(): self {
        if (!$this->has_join(subject_instance::TABLE)) {
            $this->join(subject_instance::TABLE, 'subject_instance_id', 'id');
        }

        if (!$this->has_join(track_user_assignment::TABLE)) {
            $this->join(track_user_assignment::TABLE, subject_instance::TABLE . '.track_user_assignment_id', 'id');
        }

        if (!$this->has_join(track::TABLE)) {
            $this->join(track::TABLE, track_user_assignment::TABLE . '.track_id', 'id');
        }

        if (!$this->has_join(activity::TABLE)) {
            $this->join(activity::TABLE, track::TABLE . '.activity_id', 'id');
        }

        return $this;
    }

}