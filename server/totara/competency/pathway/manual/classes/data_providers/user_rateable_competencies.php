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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\data_providers;

use core\entity\user;
use core\orm\collection;
use core\orm\query\field;
use pathway_manual\entity\rating;
use pathway_manual\models\rateable_competency;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\role_factory;
use pathway_manual\models\user_competencies;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\models\assignment_reason;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_repository;
use totara_hierarchy\entity\competency_type as competency_type_entity;

/**
 * Class user_rateable_competencies
 *
 * Fetch and arrange competencies that can be rated for a specific user and role.
 *
 * @package pathway_manual\data_providers
 */
class user_rateable_competencies extends rateable_competencies {

    /**
     * @var user|null
     */
    protected $user;

    /**
     * @var role|null
     */
    protected $role;

    /**
     * Get all rateable competencies for a particular role and assigned user.
     *
     * @param int|user $user User ID or entity
     * @param string|role $role Role string or class
     * @return self
     */
    public static function for_user_and_role($user, $role): self {
        if (!$user instanceof user) {
            $user = new user($user);
        }

        if (!$role instanceof role) {
            $role = role_factory::create($role);
        }
        $role->set_subject_user($user->id);
        $role::require_capability($user->id);
        $role::require_for_user($user->id);

        $provider = new static();
        $provider->user = $user;
        $provider->role = $role;
        $provider->add_filters([
            'user_id' => $user->id,
            'roles' => [$role::get_name()],
        ]);

        return $provider;
    }

    /**
     * Get the names of the filters that we want to display options of.
     *
     * @return string[]
     */
    protected static function get_enabled_filter_options(): array {
        return [
            'assignment_reason',
            'competency_type',
            'rating_history',
        ];
    }

    /**
     * Get the assignment reasons that are available to filter by.
     *
     * @return assignment_reason[]
     */
    protected function get_assignment_reason_filter_options() {
        $competencies = $this->items->pluck('id');

        if (count($competencies) < 2) {
            // No point in a filter without multiple options.
            return null;
        }

        $assignments = assignment::repository()
            ->join([competency_assignment_user::TABLE, 'ass_user'], 'id', 'assignment_id')
            ->where('ass_user.user_id', $this->user->id)
            ->where('competency_id', $competencies)
            ->get();

        $assignment_reasons = assignment_reason::build_from_assignments($assignments);

        if (count($assignment_reasons) < 2) {
            // No point in a filter without multiple options.
            return null;
        }

        return $assignment_reasons;
    }

    /**
     * Filter the competencies by the reason they were assigned to the user.
     *
     * @param competency_repository $repository
     * @param array $assignment_ids
     */
    protected function filter_by_assignment_reason(competency_repository $repository, array $assignment_ids) {
        $user_assigned_competencies = competency_assignment_user::repository()
            ->where_in('assignment_id', $assignment_ids)
            ->where('user_id', $this->user->id)
            ->where_field('competency_id', new field('id', $repository->get_builder()));

        $repository->where_exists($user_assigned_competencies->get_builder());
    }

    /**
     * Get the competency types that are available to filter by.
     *
     * @return competency_type_entity[]|null
     */
    protected function get_competency_type_filter_options() {
        $type_ids = array_unique($this->items->pluck('typeid'));

        if (count($type_ids) < 2) {
            // No point in a filter without multiple options.
            return null;
        }

        return competency_type_entity::repository()
            ->where_in('id', $type_ids)
            ->order_by('fullname')
            ->get()
            ->all();
    }

    /**
     * Filter the competencies by their type.
     *
     * @param competency_repository $repository
     * @param int $id Competency type ID
     */
    protected function filter_by_competency_type(competency_repository $repository, int $id) {
        $repository->where('typeid', $id);
    }

    /**
     * Get the rating statuses
     *
     * @return bool Whether to show the rating status filter or not
     */
    protected function get_rating_history_filter_options() {
        $competencies = $this->items->pluck('id');

        if ($competencies < 2) {
            // No point in a filter without multiple options.
            return null;
        }

        $number_of_competencies_with_rating = rating::repository()
            ->where_in('competency_id', $competencies)
            ->where('user_id', $this->user->id)
            ->where('assigned_by_role', $this->role::get_name())
            ->where('assigned_by', user::logged_in()->id)
            ->count();

        // If there are less rated than the total count then there are both, so enable the filter.
        return $number_of_competencies_with_rating < count($competencies) && $number_of_competencies_with_rating > 0;
    }

    /**
     * Filter the competencies by whether they have already been rated by this user or not.
     *
     * @param competency_repository $repository
     * @param bool $has_rated Either has or has not rated.
     */
    protected function filter_by_rating_history(competency_repository $repository, bool $has_rated) {
        $rating_subquery = rating::repository()
            ->select('id')
            ->where_field('competency_id', new field('id', $repository->get_builder()))
            ->where('user_id', $this->user->id)
            ->where('assigned_by_role', $this->role::get_name())
            ->where('assigned_by', user::logged_in()->id);

        if ($has_rated) {
            $repository->where_exists($rating_subquery->get_builder());
        } else {
            $repository->where_not_exists($rating_subquery->get_builder());
        }
    }

    /**
     * Run the query with any added filters and store the result.
     *
     * @return collection
     */
    public function fetch_from_query(): collection {
        return $this->build_query()
            ->with('framework.scale.sorted_values_high_to_low')
            ->order_by('fullname')
            ->get();
    }

    /**
     * Get the rateable competencies.
     *
     * @return rateable_competency[]
     */
    public function get_competencies(): array {
        return $this
            ->fetch()
            ->items
            ->map(function (competency $competency) {
                return new rateable_competency($competency, $this->user, $this->role);
            })
            ->all();
    }

    /**
     * Get the competencies available for the user.
     *
     * @return user_competencies
     */
    public function get() {
        return new user_competencies($this->user, $this->role, $this->get_competencies());
    }

    /**
     * Get the competencies available for the user, and also resolve the available filter options.
     *
     * @return user_competencies
     */
    public function get_with_filter_options(): user_competencies {
        return $this
            ->get()
            ->set_filter_options(
                $this->fetch_filter_options()
            );
    }

}
