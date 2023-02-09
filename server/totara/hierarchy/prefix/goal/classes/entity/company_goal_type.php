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
use core\orm\entity\relations\has_many;
use core\orm\entity\repository;

/**
 * Represents a company goal type record in the repository.
 *
 * @property-read int $id record id
 * @property string $shortname type name
 * @property string $description type description
 * @property int $timecreated time created
 * @property int $timemodified time modified
 * @property int $usercreated time created
 * @property int $usermodified time modified
 * @property string $fullname type name
 * @property string $idnumber type idnumber
 *
 * @property-read user $user user this goal belongs to
 * @property-read scale $scale goal scoring scale
 * @property-read scale_value $scale_value goal score
 *
 * @method static repository repository()
 */
class company_goal_type extends entity {
    public const TABLE = 'goal_type';

    /**
     * Establishes the relationship with company goal entities.
     *
     * @return has_many the relationship.
     */
    public function goals(): has_many {
        return $this->has_many(company_goal::class, 'typeid');
    }
}
