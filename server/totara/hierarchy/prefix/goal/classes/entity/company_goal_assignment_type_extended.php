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
 * @package hierarchy_goal
 */

namespace hierarchy_goal\entity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Represents a company goal to user assignment type record in the repository.
 *
 * @property-read int $id record id
 * @property int $assigntype assignment type
 * @property int $assignmentid assignment id
 * @property int $goalid assigned goal
 * @property int $userid assigned user
 * @property string $extrainfo extract assignment details
 * @property int $timemodified record modification time.
 * @property int $usermodified record modification time.
 * @property-read company_goal $goal assigned goal
 * @property-read user $user assigned user
 *
 * @method static company_goal_assignment_type_extended repository()
 */

class company_goal_assignment_type_extended extends entity {
    public const TABLE = 'goal_user_assignment';

    /**
     * Establishes the relationship with goal entities.
     *
     * @return belongs_to the relationship.
     */
    public function goal(): belongs_to {
        return $this->belongs_to(company_goal::class, 'goalid');
    }

    /**
     * Establishes the relationship with user entities.
     *
     * @return belongs_to the relationship.
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'userid');
    }
}
