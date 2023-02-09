<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_core\entity\relationship;

/**
 * Represents the final participant for a manually selected role.
 *
 * @property-read int $id record id
 * @property int $subject_instance_id parent subject instance
 * @property int $core_relationship_id parent core relationship
 * @property int $user_id assigned internal participant.
 * @property string $name assigned external participant name.
 * @property string $email assigned external participant email.
 * @property int $created_by userid who did assignment
 * @property int $created_at record creation time
 *
 * @property-read subject_instance $subject_instance
 * @property-read relationship $core_relationship
 * @property-read user $user
 *
 * @method static subject_instance_manual_participant_repository repository()
 */

class subject_instance_manual_participant extends entity {
    public const TABLE = 'perform_subject_instance_manual_participant';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    /**
     * Returns the parent subject instance.
     *
     * @return belongs_to the relationship.
     */
    public function subject_instance(): belongs_to {
        return $this->belongs_to(subject_instance::class, 'subject_instance_id');
    }

    /**
     * Returns the associated totara core relationship.
     *
     * @return belongs_to the relationship.
     */
    public function core_relationship(): belongs_to {
        return $this->belongs_to(relationship::class, 'core_relationship_id');
    }

    /**
     * Returns the assigned user who will participate in the activity.
     *
     * @return belongs_to the relationship.
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }
}
