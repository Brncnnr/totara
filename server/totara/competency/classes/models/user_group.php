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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use core\orm\collection;
use core\orm\entity\entity;

/**
 * Abstract model for an assignment user group.
 *
 * A user group for example can be a user, a position, etc. containing one or multiple users.
 */
abstract class user_group {

    /**
     * @var bool
     */
    protected $is_deleted = false;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var int
     */
    protected $id;

    /**
     * Initiate this model via static factory methods
     *
     * @param int $id
     * @param string $name
     * @param bool $is_deleted
     */
    protected function __construct(int $id, string $name, bool $is_deleted) {
        $this->id = $id;
        $this->name = $name;
        $this->is_deleted = $is_deleted;
    }

    /**
     * Static factory method returning a new instance
     *
     * @param int $id
     * @return user_group
     */
    abstract public static function load_by_id(int $id): self;

    /**
     * Static factory method returning a new instance
     *
     * @param entity $entity
     * @return user_group
     */
    abstract public static function load_by_entity(?entity $entity): self;

    /**
     * Return a collection of user group entities (i.e. position, cohort, user, organization)
     *
     * @param array $ids
     * @return collection
     */
    abstract public static function load_user_groups(array $ids): collection;

    public function get_id(): string {
        return $this->id;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function is_deleted(): bool {
        return $this->is_deleted;
    }

    /**
     * Get type string, see type constants in \totara_competency\user_groups class for examples
     *
     * @return string
     */
    abstract public function get_type(): string;

    /**
     * Returns true if the current user has the right capabilities to view the user group
     * @return bool
     */
    abstract public function can_view(): bool;

}