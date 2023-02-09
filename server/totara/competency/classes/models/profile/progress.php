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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\models\profile;

use core\orm\collection;
use stdClass;
use core\entity\user;
use totara_competency\data_providers\assignments;
use totara_competency\entity\competency_achievement;

/**
 * This is a generic profile progress model scaffolding, it has the following properties available:
 *
 *  - User -> user entity
 *  - Items -> [Progress item] - a collection of objects containing individual progress items per assignments grouped
 *                               by almost user group name (it's slightly more conditional)
 *  - Filters -> [Filter] - a collection of filter items
 *  - Latest achievement -> Competency achievement entity - Latest competency achieved by user (if any)
 *
 * @property-read user $user User the progress is for
 * @property-read collection $items Collection of progress items
 * @property-read collection $filters Collection of filters
 * @property-read string $latest_achievement Latest achieved competency name (if any)
 * @package totara_competency\models
 */
class progress {

    /**
     * User class
     *
     * @var stdClass
     */
    protected $user;

    /**
     * Progress items
     *
     * @var collection
     */
    protected $items;

    /**
     * Filters
     *
     * @var array
     */
    protected $filters;

    /**
     * Latest achieved competency name
     *
     * @var string|null
     */
    protected $latest_achievement;

    /**
     * Array of attributes available for public (read-only) access
     *
     * @var array
     */
    protected $public_attributes = [
        'user',
        'items',
        'filters',
        'latest_achievement',
    ];

    /**
     * Progress item for a user
     *
     * @param int $user_id
     * @param array $filters
     */
    public function __construct(int $user_id, array $filters = []) {
        // To build this information we need:

        $assignments = assignments::for($user_id)->set_filters($filters)->fetch();

        // This converts user to an stdClass, there is a stupid hardcoded check for stdClasses
        $this->user = $assignments->get_user()->to_record();

        $this->items = item::build_from_assignments_provider($assignments);

        $this->filters = filter::build_from_assignments_provider(assignments::for($user_id)->fetch());

        $this->latest_achievement = $this->get_latest_achievement_name($user_id);
    }

    /**
     * Glorified constructor
     *
     * @param int $user_id
     * @param array $filters
     * @return static
     */
    public static function for(int $user_id, array $filters = []) {
        return new static($user_id, $filters);
    }

    /**
     * Attributes getter
     * Allows read-only access to a subset of properties
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        // ?? will trigger isset and check for public attributes only
        return $this->{$name} ?? null;
    }

    /**
     * Return that publicly available attributes are set
     *
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        return in_array($name, $this->public_attributes);
    }

    /**
     * Attribute setter, just throws an exception
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        throw new \coding_exception('Progress is a read only model');
    }

    /**
     * Get latest achieved competency name for a given user id
     *
     * @param int $user_id
     * @return string
     */
    protected function get_latest_achievement_name(int $user_id): ?string {
        $achievement = competency_achievement::repository()
            ->with('competency')
            ->where('proficient', true)
            ->where('status', [competency_achievement::ACTIVE_ASSIGNMENT, competency_achievement::ARCHIVED_ASSIGNMENT])
            ->where('user_id', $user_id)
            ->order_by('time_created', 'desc')
            ->first();

        return $achievement->competency->fullname ?? null;
    }
}
