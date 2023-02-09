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
use core\orm\entity\relations\has_one_through;

/**
 * Represents the actual user who will select the participants for a subject instance.
 *
 * @property-read int $id record id
 * @property int $manual_relation_select_progress_id associated selection progress
 * @property int $user_id assigned participant.
 * @property int $notified_at notification time
 * @property int $created_at record creation time
 *
 * @property-read manual_relationship_selection_progress $progress
 * @property-read user $user
 * @property-read subject_instance $subject_instance
 *
 * @method static manual_relationship_selector_repository repository()
 */

class manual_relationship_selector extends entity {
    public const TABLE = 'perform_manual_relation_selector';
    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * Returns the associated manual selection progress record.
     *
     * @return belongs_to the relationship.
     */
    public function progress(): belongs_to {
        return $this->belongs_to(manual_relationship_selection_progress::class, 'manual_relation_select_progress_id');
    }

    /**
     * Returns the assigned user who will participate in the activity.
     *
     * @return belongs_to the relationship.
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }

    /**
     * @return has_one_through
     */
    public function subject_instance(): has_one_through {
        return $this->has_one_through(
            manual_relationship_selection_progress::class,
            subject_instance::class,
            'manual_relation_select_progress_id',
            'id',
            'subject_instance_id',
            'id'
        );
    }
}
