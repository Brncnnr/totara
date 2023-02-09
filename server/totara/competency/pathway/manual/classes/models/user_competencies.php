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

namespace pathway_manual\models;

use core\entity\user;
use pathway_manual\data_providers\rateable_competencies;
use pathway_manual\models\roles\role;

/**
 * Class user_competencies
 *
 * @package pathway_manual\models
 */
class user_competencies {

    /**
     * @var rateable_competency[]
     */
    protected $competencies;

    /**
     * @var user
     */
    protected $user;

    /**
     * @var role
     */
    protected $role;

    /**
     * @var array
     */
    protected $filter_options;

    /**
     * @param user $user
     * @param role $role
     * @param rateable_competency[] $competencies
     */
    public function __construct(user $user, role $role, array $competencies) {
        $this->user = $user;
        $this->role = $role;
        $this->competencies = $competencies;
    }

    /**
     * Get the framework groups.
     *
     * @return framework_group[]
     */
    public function get_framework_groups(): array {
        return framework_group::build_from_competencies($this->competencies);
    }

    /**
     * Get the user the competencies are assigned to.
     *
     * @return user
     */
    public function get_user_for(): user {
        return $this->user;
    }

    /**
     * Get the role the competencies have a pathway for.
     *
     * @return role
     */
    public function get_role(): role {
        return $this->role;
    }

    /**
     * Get the total number of competencies available.
     *
     * @return int
     */
    public function get_count(): int {
        return count($this->competencies);
    }

    /**
     * Can the current user rate competencies for the specified user?
     *
     * @param int $for_user User ID
     * @return bool
     */
    public static function can_rate_competencies(int $for_user) {
        /** @var role[] $roles */
        $roles = roles::get_current_user_roles($for_user);

        /** @var role $role */
        foreach ($roles as $role) {
            if ($role->has_capability($for_user)) {
                $rateable_competencies = (new rateable_competencies())->add_filters([
                    'user_id' => $for_user,
                    'roles' => [$role::get_name()],
                ]);
                if ($rateable_competencies->count() > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Set the filter options to also return.
     *
     * @param array $filter_options
     * @return self
     */
    public function set_filter_options(array $filter_options): self {
        $this->filter_options = $filter_options;

        return $this;
    }

    /**
     * Get the filter options for the user.
     *
     * @return array|null
     */
    public function get_filter_options(): ?array {
        return $this->filter_options ?? null;
    }

}
