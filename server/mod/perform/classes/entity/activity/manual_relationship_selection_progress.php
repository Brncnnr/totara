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

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;

/**
 * Indicates how the choosing of manually participant roles has progressed.
 *
 * @property-read int $id record id
 * @property int $subject_instance_id associated subject instance
 * @property int $manual_relation_selection_id details of the selector/participant roles.
 * @property bool $status True=manual participant selection is completed for this subject/participant/selector, false= no
 * @property int $created_at record creation time
 * @property int $updated_at record modification time
 *
 * @property-read subject_instance $subject_instance
 * @property-read manual_relationship_selection $manual_relationship_selection
 * @property-read manual_relationship_selector[]|collection $manual_relationship_selectors
 *
 * @method static manual_relationship_selection_progress_repository repository()
 */

class manual_relationship_selection_progress extends entity {
    public const TABLE = 'perform_manual_relation_selection_progress';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    public const STATUS_PENDING = 0;
    public const STATUS_COMPLETE = 1;

    /**
     * Returns the parent subject instance.
     *
     * @return belongs_to the relationship.
     */
    public function subject_instance(): belongs_to {
        return $this->belongs_to(subject_instance::class, 'subject_instance_id');
    }

    /**
     * Returns the manual selection instance.
     *
     * @return belongs_to the relationship.
     */
    public function manual_relationship_selection(): belongs_to {
        return $this->belongs_to(manual_relationship_selection::class, 'manual_relation_selection_id');
    }

    /**
     * Returns the users who select the participants for the relationship.
     *
     * @return has_many the relationship.
     */
    public function manual_relationship_selectors(): has_many {
        return $this->has_many(manual_relationship_selector::class, 'manual_relation_select_progress_id');
    }

    /**
     * "status" field getter. Needed because the read from the DB _returns strings
     * and the ORM does not convert the value_.
     *
     * TBD: to remove once the base_entity handles this.
     *
     * @param int|null $value incoming value.
     *
     * @return bool the converted value.
     */
    public function get_status_attribute(?int $value = null): bool {
        return (bool)$value;
    }
}
