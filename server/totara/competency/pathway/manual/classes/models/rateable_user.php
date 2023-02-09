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
use core\orm\entity\entity;
use pathway_manual\models\roles\role;
use pathway_manual\entity\rating;

/**
 * Class rateable_user
 *
 * @package pathway_manual\models
 */
class rateable_user {

    /**
     * @var user
     */
    protected $user;

    /**
     * @var role
     */
    protected $role;

    /**
     * @var int
     */
    protected $competency_count;

    /**
     * @param user $user
     * @param role $role
     * @param int $competency_count
     */
    public function __construct(user $user, role $role, int $competency_count) {
        $this->user = $user;
        $this->role = $role;
        $this->competency_count = $competency_count;
    }

    /**
     * Get the user.
     *
     * @return user
     */
    public function get_user(): user {
        return $this->user;
    }

    /**
     * Get the number of competencies that can be rated for this user.
     *
     * @return int
     */
    public function get_competency_count(): int {
        return $this->competency_count;
    }

    /**
     * Get the last rating made for this user in this role.
     *
     * @return rating|entity|null
     */
    public function get_latest_rating(): ?rating {
        return rating::repository()
            ->where('user_id', $this->user->id)
            ->where('assigned_by_role', $this->role::get_name())
            ->order_by('date_assigned', 'DESC')
            ->first();
    }

}
