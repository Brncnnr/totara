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

use goal;
use totara_hierarchy\entity\hierarchy_item;
use core\orm\entity\relations\belongs_to;

/**
 * Represents a company goal record in the repository.
 *
 * @property-read int $id record id
 * @property string $shortname Short name
 * @property string $description goal description
 * @property string $idnumber id number
 * @property int $frameworkid framework ID
 * @property string $path goal path in the hierarchy
 * @property int $parentid parent goal ID
 * @property bool $visible visibility flag
 * @property int $targetdate expected achievement date
 * @property int $proficiencyexpected expected proficiency
 * @property int $timecreated time created
 * @property int $timemodified time modified
 * @property int $usermodified time modified
 * @property string $fullname full goal name
 * @property int $depthlevel depth level in the hierarchy
 * @property int $typeid goal type ID
 * @property string $sortthread sort order
 * @property-read company_goal_type $type company goal type
 * @property-read string $goal_scope
 */
class company_goal extends hierarchy_item {
    public const TABLE = 'goal';

    protected $extra_attributes = [
        'goal_scope',
    ];

    /**
     * Return the goal scope
     *
     * @return string
     */
    protected function get_goal_scope_attribute(): string {
        return goal::GOAL_SCOPE_COMPANY;
    }

    /**
     * Mutates (possibly) null target dates.
     *
     * @param mixed $value the raw target date.
     */
    protected function set_targetdate_attribute($value): void {
        $this->set_attribute_raw(
            'targetdate', is_null($value) ? 0 : $value, false
        );
    }

    /**
     * Establishes the relationship with user entities.
     *
     * @return belongs_to the relationship.
     */
    public function type(): belongs_to {
        return $this->belongs_to(company_goal_type::class, 'typeid');
    }

}
