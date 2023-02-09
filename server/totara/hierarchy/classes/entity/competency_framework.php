<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_hierarchy
 */

namespace totara_hierarchy\entity;

use core\orm\entity\relations\has_one_through;
use core\orm\collection;

/**
 * @property string $shortname
 * @property string $idnumber
 * @property string $description
 * @property int $sortorder
 * @property int $visible
 * @property int $hidecustomfields
 * @property int $timecreated
 * @property int $timemodified
 * @property int $usermodified
 * @property string $fullname
 *
 * @property-read scale $scale
 *
 * @method static competency_framework_repository repository()
 */
class competency_framework extends hierarchy_framework {

    public const TABLE = 'comp_framework';

    /**
     * Get related scale
     *
     * @return has_one_through
     */
    public function scale(): has_one_through {
        return $this->has_one_through(
            scale_assignment::class,
            scale::class,
            'id',
            'frameworkid',
            'scaleid',
            'id'
        );
    }

    public function get_competencies_attribute(): collection {
        return competency::repository()
            ->where('frameworkid', $this->id)
            ->order_by('sortthread')
            ->get();
    }

}
