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

use core\orm\entity\entity;

/**
 * Hierarchy framework base entity
 *
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
 * @method static hierarchy_framework_repository repository()
 */
abstract class hierarchy_framework extends entity {

    const CREATED_TIMESTAMP = 'timecreated';
    const UPDATED_TIMESTAMP = 'timemodified';

    const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Extra attributes to append
     *
     * @var array
     */
    protected $extra_attributes = [
        'display_name'
    ];

    /**
     * Return display name
     *
     * @return string
     */
    protected function get_display_name_attribute() {
        return $this->fullname;
    }
}