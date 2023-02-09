<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;

/**
 * @property int $contextid
 * @property string $name
 * @property string $idnumber
 * @property string $description
 * @property int $descriptionformat
 * @property bool $visible
 * @property string $component
 * @property int $timecreated
 * @property int $cohorttype
 * @property int $modifierid
 * @property int $timemodified
 * @property bool $visibility
 * @property int $alertmembers
 * @property int $startdate
 * @property int $enddate
 * @property bool $active
 * @property int $calculationstatus
 * @property int $activecollectionid
 * @property int $draftcollectionid
 * @property bool $broken
 * @property int $members_count
 * @property-read string $display_name
 * @property-read collection|cohort_member[] $members
 *
 * @method static cohort_repository repository()
 *
 * @package core
 */
class cohort extends entity implements expandable {

    use expand;

    protected const EXPAND_TABLE = 'cohort_members';
    protected const EXPAND_SELECT_COLUMN = 'userid';
    protected const EXPAND_QUERY_COLUMN = 'cohortid';

    public const TABLE = 'cohort';
    public const CREATED_TIMESTAMP = 'timecreated';
    public const UPDATED_TIMESTAMP = 'timemodified';
    public const SET_UPDATED_WHEN_CREATED = true;

    protected $extra_attributes = [
        'display_name',
        'members_count',
    ];

    /**
     * Get unified display name that can be referred to safely, just an alias in this case
     *
     * @return string
     */
    protected function get_display_name_attribute() {
        return $this->name;
    }

    /**
     * Number of members in the cohort.
     *
     * @return int
     */
    public function get_members_count_attribute(): int {
        $attributes = $this->get_attributes_raw();

        return empty($attributes['members_count'])
            ? self::repository()->get_members_count($this->id)
            : $attributes['members_count'];
    }

    /**
     * Get cohort members relation
     *
     * @return has_many
     */
    public function members(): has_many {
        return $this->has_many(cohort_member::class, 'cohortid');
    }
}
