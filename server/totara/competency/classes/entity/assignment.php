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
 * @package totara_competency
 */

namespace totara_competency\entity;

use coding_exception;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use core\entity\user;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * Assignment entity
 *
 * @property string $type assignment type
 * @property int $competency_id Competency ID
 * @property string $user_group_type Type of a linked user group
 * @property int $user_group_id ID of a linked user group
 * @property bool $optional Optional flag
 * @property int $status Assignment status eg (0 - draft, 1 - published, etc)
 * @property-read string progress_name Assignment name
 * @property int $created_by ID of the created user
 * @property int $created_at Created at timestamp
 * @property int $updated_at Updated at timestamp
 * @property int $archived_at Archived at timestamp
 * @property bool $expand Flag whether to expand assignment on next expand task run
 * @property int|null $minproficiencyid Minimum proficiency override value for the assignment
 *
 * @property-read string $status_name Textual representation of status int
 * @property-read competency_achievement $current_achievement Current achievement
 * @property-read collection $current_achievements Current achievements
 * @property-read collection $achievements All achievements
 * @property-read competency $competency
 * @property-read user $assigner
 * @property-read collection $assignment_users
 * @property-read competency_assignment_user $assignment_user
 * @property-read scale_value $min_proficient_value_override
 *
 * @method static assignment_repository repository()
 *
 * @package totara_competency\entity
 */
class assignment extends entity {

    public const STATUS_DRAFT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_ARCHIVED = 2;

    public const STATUS_NAME_DRAFT = 'draft';
    public const STATUS_NAME_ACTIVE = 'active';
    public const STATUS_NAME_ARCHIVED = 'archived';

    // assigned by admin users via the interface
    public const TYPE_ADMIN = 'admin';

    // assigned by the user themselves
    public const TYPE_SELF = 'self';

    // assigned by other users, like managers, for a user
    public const TYPE_OTHER = 'other';

    // assigned automatically by the system due to the continuous tracking functionality
    public const TYPE_SYSTEM = 'system';

    // Assignments to account for pre-perform achievements, archived when created...
    public const TYPE_LEGACY = 'legacy';

    public const TABLE = 'totara_competency_assignments';

    protected $extra_attributes = [
        'status_name',
    ];

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * @return array
     */
    public static function get_available_types() {
        return [
            self::TYPE_ADMIN,
            self::TYPE_SELF,
            self::TYPE_OTHER,
            self::TYPE_SYSTEM
        ];
    }

    /**
     * Related competency
     *
     * @return belongs_to
     */
    public function competency(): belongs_to {
        return $this->belongs_to(competency::class, 'competency_id');
    }

    /**
     * All competency achievements
     *
     * @return has_many
     */
    public function user_logs(): has_many {
        return $this->has_many(competency_assignment_user_log::class, 'assignment_id');
    }

    /**
     * All competency achievements
     *
     * @return has_many
     */
    public function achievements(): has_many {
        return $this->has_many(competency_achievement::class, 'assignment_id');
    }

    /**
     * Current competency achievements
     *
     * @return has_many
     */
    public function current_achievements(): has_many {
        return $this->achievements() ->where('status', 'in', [competency_achievement::ACTIVE_ASSIGNMENT, competency_achievement::ARCHIVED_ASSIGNMENT]);
    }

    /**
     * Current competency achievement
     * This is meant to be used with a user filter, otherwise it will just give you a first random achievement
     *
     * @return has_one
     */
    public function current_achievement(): has_one {
        return $this->has_one(competency_achievement::class, 'assignment_id')
            ->where('status', 'in', [competency_achievement::ACTIVE_ASSIGNMENT, competency_achievement::ARCHIVED_ASSIGNMENT]);
    }

    /**
     * One assignment user
     *
     * This is meant to be used with the user filter, otherwise you'd just get a first random user...
     *
     * @return has_one
     */
    public function assignment_user(): has_one {
        return $this->has_one(competency_assignment_user::class, 'assignment_id');
    }

    /**
     * All assignment users
     *
     * @return has_many
     */
    public function assignment_users(): has_many {
        return $this->has_many(competency_assignment_user::class, 'assignment_id');
    }

    /**
     * Get the user who created the assignment
     *
     * @return belongs_to
     */
    public function assigner(): belongs_to {
        return $this->belongs_to(user::class, 'created_by');
    }

    /**
     * The min proficient scale value override (if one is set).
     *
     * @return has_one
     */
    public function min_proficient_value_override(): has_one {
        return $this->has_one(scale_value::class, 'id', 'minproficiencyid');
    }


    /**
     * Get status attribute
     *
     * @param int $status
     * @return int
     */
    public function get_status_attribute($status = 0): int {
        return (int) $status;
    }

    /**
     * Get status name attribute
     *
     * @return string
     */
    public function get_status_name_attribute(): string {
        switch ($this->status) {
            case assignment::STATUS_DRAFT:
                $name = self::STATUS_NAME_DRAFT;
                break;
            case assignment::STATUS_ACTIVE:
                $name = self::STATUS_NAME_ACTIVE;
                break;
            case assignment::STATUS_ARCHIVED:
                $name = self::STATUS_NAME_ARCHIVED;
                break;
            default:
                throw new coding_exception("Unknown assignment status '{$this->status}'");
                break;
        }
        return $name;
    }

    /**
     * Accessor to convert expand flag to boolean
     *
     * @param $value
     * @return bool
     */
    public function get_expand_attribute($value): bool {
        return (bool)$value;
    }

    /**
     * Mutator to convert boolean to integer flag
     *
     * @param bool $value
     */
    public function set_expand_attribute($value) {
        $this->set_attribute_raw('expand', (int) $value);
    }

}
