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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual;
 */

namespace pathway_manual\entity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use pathway_manual\models\roles\role_factory;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * Class rating
 *
 * Acts as a record of a user having rated another user, or themselves, along a scale for a given competency.
 *
 * @property-read int $id ID
 * @property int $competency_id
 * @property int $user_id
 * @property int $scale_value_id
 * @property int $date_assigned
 * @property int $assigned_by
 * @property string $assigned_by_role
 * @property string $comment
 *
 * @property-read user $assigned_by_user
 * @property-read scale_value $scale_value
 */
class rating extends entity {

    public const TABLE = 'pathway_manual_rating';

    /**
     * User who made the rating.
     *
     * @return belongs_to
     */
    public function assigned_by_user(): belongs_to {
        return $this->belongs_to(user::class, 'assigned_by')
            ->where('deleted', 0);
    }

    /**
     * The competency this rating is of.
     *
     * @return belongs_to
     */
    public function competency(): belongs_to {
        return $this->belongs_to(competency::class, 'competency_id');
    }

    /**
     * The value of the rating.
     *
     * @return belongs_to
     */
    public function scale_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'scale_value_id');
    }

    /**
     * Get the role definition class.
     *
     * @return \pathway_manual\models\roles\role
     */
    public function get_role(): \pathway_manual\models\roles\role {
        return role_factory::create($this->assigned_by_role);
    }

}
