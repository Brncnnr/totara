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
use core\entity\user_repository;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\subquery;
use pathway_manual\entity\role as role_entity;
use pathway_manual\manual;
use pathway_manual\models\rateable_user;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\role_factory;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\pathway;

/**
 * Class rateable_users
 *
 * Fetch and arrange users that can be rated for a specific role.
 *
 * @package pathway_manual\data_providers
 */
class rateable_users extends provider {

    /**
     * @var role|null
     */
    protected $role;

    /**
     * Get all rateable users for a particular role.
     *
     * @param string|role $role Role string or class
     * @return self
     */
    public static function for_role($role): self {
        if (!$role instanceof role) {
            $role = role_factory::create($role);
        }

        if ($role instanceof self_role) {
            throw new \coding_exception('Role \'self\' is not allowed here!');
        }

        $provider = new static();
        $provider->role = $role;

        return $provider;
    }

    /**
     * Get a subquery of how many rateable competencies a user has.
     *
     * @param user_repository $repository
     * @return subquery
     */
    private function get_competency_count_query(user_repository $repository): subquery {
        $competency_count = new subquery(function (builder $builder) use ($repository) {
            $builder
                ->select_raw('COUNT(DISTINCT(cau.competency_id))')
                ->from(competency_assignment_user::TABLE, 'cau')
                ->where_field('user_id', new field('id', $repository->get_builder()));
        });

        $valid_manual_pathways = pathway::repository()
            ->as('pw')
            ->select('id')
            ->join([role_entity::TABLE, 'role'], 'path_instance_id', 'path_manual_id')
            ->where('path_type', 'manual')
            ->where('status', manual::PATHWAY_STATUS_ACTIVE)
            ->where('role.role', $this->role::get_name())
            ->where_field('pw.competency_id', new field('cau.competency_id', $competency_count->get_builder()));

        $competency_count
            ->get_subquery()
            ->where_exists($valid_manual_pathways->get_builder());

        return $competency_count;
    }

    /**
     * @param user_repository|repository $repository
     * @param string $substring
     */
    protected function filter_by_user_full_name(repository $repository, string $substring) {
        $repository->filter_by_full_name($substring);
    }

    /**
     * Build the rateable users query.
     *
     * @return user_repository|repository
     */
    protected function build_query(): repository {
        $repository = user::repository()
            ->select_full_name_fields()
            ->select_user_picture_fields()
            ->filter_by_not_guest()
            ->filter_by_not_deleted()
            ->filter_by_not_current_user()
            ->order_by_full_name();

        $this->role::apply_role_restriction_to_builder($repository);

        $repository->add_select(
            $this->get_competency_count_query($repository)
                ->as('competency_count')
        );

        $this->apply_filters($repository);

        return $repository;
    }

    /**
     * Get the competencies available for the user.
     *
     * @return rateable_user[]
     */
    public function get(): array {
        return $this
            ->fetch()
            ->items
            ->filter(function (user $user) {
                return $this->role::has_capability($user->id) && $user->competency_count > 0;
            })
            ->map(function (user $user) {
                return new rateable_user($user, $this->role, $user->competency_count);
            })
            ->all();
    }

}
