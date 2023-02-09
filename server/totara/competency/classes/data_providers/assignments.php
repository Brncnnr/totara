<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\data_providers;

use core\orm\entity\repository;
use core\orm\pagination\cursor_paginator;
use core\orm\query\builder;
use core\orm\query\field;
use core\pagination\cursor;
use totara_competency\entity\assignment;
use totara_competency\entity\assignment_repository;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\competency_assignment_user;
use totara_competency\user_groups;
use totara_hierarchy\entity\competency;

/**
 * Class assignments
 *
 * This class is responsible for fetching assignment for a particular user including
 * a lot of relevant data (Competency, Scale, Scale values, Achievement details, Min proficient value, etc
 * as well as filtering assignments and ordering them in a particular way.
 *
 * @package totara_competency\data_providers
 */
class assignments extends user_data_provider {

    /**
     * Fetch assignments from the database, applying filters and order
     *
     * @return $this
     */
    public function fetch() {
        $repo = $this->build_query();

        $this->apply_filters($repo);

        $this->items = $repo->get();

        $this->order_assignments();

        return $this;
    }

    /**
     * Fetch a paginated list of assignments.
     *
     * @param string|null $cursor
     * @param int|null $limit
     * @return cursor_paginator
     */
    public function fetch_paginated(?string $cursor, ?int $limit): cursor_paginator {
        $page_cursor = cursor::create()->set_limit($limit ?? cursor_paginator::DEFAULT_ITEMS_PER_PAGE);
        if ($cursor) {
            $page_cursor = cursor::decode($cursor);
        }

        $query = $this->build_query();
        $this->apply_filters($query);

        $paginator = new cursor_paginator($query, $page_cursor, true);
        $paginator->get();

        return $paginator;
    }

    /**
     * Build the query
     *
     * @return assignment_repository
     */
    protected function build_query(): assignment_repository {
        $only_current = null;
        if (isset($this->filters['status'])) {
            if ($this->filters['status'] === assignment::STATUS_ACTIVE) {
                $only_current = true;
            } else if ($this->filters['status'] === assignment::STATUS_ARCHIVED) {
                $only_current = false;
            }
        }

        // Let's outline the relations we want to fetch the assignments with
        return $this->assignments_repository_for_user($only_current)
            ->with([
                'competency' => function (repository $repository) {
                    $repository->with([
                        'scale' => function (repository $repository) {
                            $repository->with('values')
                                ->with('sorted_values_high_to_low')
                                ->with('min_proficient_value');
                        }
                    ]);
                },
                'current_achievement' => function (repository $repository) {
                    $repository->where('user_id', $this->get_user()->id)
                        ->with('value');
                },
                'assignment_user' => function (repository $repository) {
                    $repository->where('user_id', $this->get_user()->id);
                },
            ])
            // The following is necessary in order to support cursor based pagination.
            ->join([competency::TABLE, 'competency'], 'competency_id', 'id')
            ->select([
                '*',
                'competency.fullname AS competency_fullname',
            ])
            ->order_by('competency.fullname')
            ->order_by('competency.id');
    }

    /**
     * @deprecated since Totara 14
     * @return $this
     */
    protected function fetch_assignments() {
        debugging(
            'fetch_assignments() is deprecated and should no longer be used. Please use fetch() directly instead.',
            DEBUG_DEVELOPER
        );
        return $this->fetch();
    }

    /**
     * Order competency assignments for profile
     *
     * @return $this
     */
    public function order_assignments() {
        $this->items->sort(\Closure::fromCallable([$this, 'order_assignments_callback']));

        return $this;
    }

    /**
     * Callback for sorting assignments in the way required for ordering assignments in the profile.
     *
     * The algorithm sorts assignments in the following order.
     *
     * Status [asc] (Active before archived)
     * Type [asc] (Admin, other, self, system)
     * User group type [asc] (Position, organisation, audience, individual)
     * Assignment creation date [desc] (Latest first)
     * Competency name (Alphabetically)
     *
     * @param assignment $first
     * @param assignment $second
     * @return int
     */
    protected function order_assignments_callback(assignment $first, assignment $second) {
        $type_map = [
            assignment::TYPE_ADMIN => 0,
            assignment::TYPE_OTHER => 1,
            assignment::TYPE_SELF => 2,
            assignment::TYPE_SYSTEM => 3,
            assignment::TYPE_LEGACY => 4,
        ];

        $ug_type_map = [
            user_groups::POSITION => 0,
            user_groups::ORGANISATION => 1,
            user_groups::COHORT => 2,
            user_groups::USER => 3,
        ];

        // Let's compare status
        if ($first->status != $second->status) {
            return $first->status <=> $second->status;
        }

        // Let's compare types first
        if ($first->type != $second->type) {
            return $type_map[$first->type] <=> $type_map[$second->type];
        }

        // Let's compare user group first
        if ($first->user_group_type != $second->user_group_type) {
            return $ug_type_map[$first->user_group_type] <=> $ug_type_map[$second->user_group_type];
        }

        // Then assignment type is the same, let's compare assignment creation date then
        if ($first->created_at != $second->created_at) {
            // Most recent first
            return $second->created_at <=> $first->created_at;
        }

        // Checking that competency relation is loaded to avoid triggering a lot of extra database queries
        if ($first->relation_loaded('competency') && $second->relation_loaded('competency')) {
            if ($first->competency->fullname !== $second->competency->fullname) {
                return $second->competency->fullname <=> $first->competency->fullname;
            }
        }

        // All is lost, we can't figure out their exact order.
        return 0;
    }

    /**
     * Get the assignments repository with basic filters applied.
     * It will ensure that we'll have all assignments for a user
     * including current assignments either via various user groups
     * or direct as well as archived assignments.
     *
     * @param bool|null $only_current
     * @return assignment_repository
     */
    protected function assignments_repository_for_user(?bool $only_current = null): assignment_repository {
        return assignment::repository()
            ->where(function (builder $builder) use ($only_current) {
                if ($only_current === true || $only_current === null) {
                    $current = builder::table(competency_assignment_user::TABLE)
                        ->where('user_id', $this->get_user()->id)
                        ->where_field('assignment_id', new field('id', builder::table(assignment::TABLE)));

                    $builder->where_exists($current);
                }

                // In case we don't filter by any or only want archived
                if (!$only_current) {
                    $archived = builder::table('totara_competency_assignment_user_logs')
                        ->where('user_id', $this->get_user()->id)
                        ->where_field('assignment_id', new field('id', builder::table(assignment::TABLE)));

                    $builder->or_where_exists($archived);
                }
            });
    }

    /**
     * Apply filters to a given repository
     *
     * @param assignment_repository $repository Repository to apply filters
     * @return $this
     */
    protected function apply_filters(assignment_repository $repository) {
        foreach ($this->filters as $key => $value) {
            // We'll only apply a filter if it has a not-nullable value)
            if (is_null($value)) {
                continue;
            }

            if (method_exists($this, $method = 'filter_by_' . $key)) {
                $this->{$method}($repository, $value);
            } else {
                throw new \moodle_exception('error_filter_assignment_not_supported', 'totara_competency', '', $key);
            }
        }

        return $this;
    }

    /**
     * Filter by assignment status
     *
     * @param assignment_repository $repository
     * @param int $value
     */
    protected function filter_by_status(assignment_repository $repository, $value) {
        // Treat active assignments with not user assignment records as archived
        if ($value === assignment::STATUS_ARCHIVED) {
            $repository->where(
                function (builder $builder) use ($value) {
                    $exists_builder = builder::table(competency_assignment_user::TABLE)
                        ->where_field('assignment_id', new field('id', $builder))
                        ->where('user_id', $this->get_user()->id);

                    $builder->where_not_exists($exists_builder)
                        ->or_where('status', intval($value));
                }
            );
        } else {
            $repository->where('status', intval($value));
        }
    }

    /**
     * Filter by assignment type
     *
     * @param assignment_repository $repository
     * @param $value
     */
    protected function filter_by_type(assignment_repository $repository, $value) {
        $repository->where('type', $value);
    }

    /**
     * Filter by assignment user group type
     *
     * @param assignment_repository $repository
     * @param $value
     */
    protected function filter_by_user_group_type(assignment_repository $repository, $value) {
        $repository->where('user_group_type', $value);
    }

    /**
     * Filter by assignment user group id
     *
     * @param assignment_repository $repository
     * @param $value
     */
    protected function filter_by_user_group_id(assignment_repository $repository, $value) {
        $repository->where('user_group_id', intval($value));
    }

    /**
     * Filter by competency id
     *
     * @param assignment_repository $repository
     * @param int $value
     */
    protected function filter_by_competency_id(assignment_repository $repository, int $value) {
        $repository->where('competency_id', intval($value));
    }

    /**
     * Search by competency name or description
     * This is a stupid like "filtering", no fts or anything fancy
     *
     * @param assignment_repository $repository
     * @param $value
     */
    protected function filter_by_search(assignment_repository $repository, $value) {
        $repository->where(function (builder $builder) use ($value) {
            $builder->where('competency.fullname', 'ilike', $value)
                ->or_where('competency.description', 'ilike', $value);
        });
    }

    /**
     * Filter by a set of assignment IDs
     *
     * @param assignment_repository $repository
     * @param int[] $assignment_ids
     */
    protected function filter_by_ids(assignment_repository $repository, array $assignment_ids): void {
        $repository->where_in('id', $assignment_ids);
    }

    /**
     * Filter by proficient value
     *
     * @param assignment_repository $repository
     * @param $value
     */
    protected function filter_by_proficient(assignment_repository $repository, $value) {
        $repository->join([competency_achievement::TABLE, 'current_achievement'], function (builder $builder) use ($repository) {
            $builder->where_field(new field('competency_id', $repository->get_builder()), 'current_achievement.competency_id')
                ->where_field(new field('id', $repository->get_builder()), 'current_achievement.assignment_id')
                ->where('current_achievement.status', competency_achievement::ACTIVE_ASSIGNMENT)
                ->where('current_achievement.user_id', $this->get_user()->id);
        })
            ->where('current_achievement.proficient', intval($value));
    }

}
