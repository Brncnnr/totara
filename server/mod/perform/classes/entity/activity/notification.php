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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;

/**
 * Notification entity
 *
 * Properties:
 * @property-read integer $id ID
 * @property integer $activity_id parent activity record id
 * @property string $class_key array key registered in db/notifications.php
 * @property string $triggers triggers in JSON format; the internal structure varies on a broker
 * @property boolean $active is active?
 * @property integer|null $last_run_at
 * @property integer $created_at
 * @property integer|null $updated_at
 *
 * Relationships:
 * @property-read activity $activity
 * @property-read collection|notification_recipient[] $recipients
 *
 * @package mod_perform\entity
 */
class notification extends entity {
    public const TABLE = 'perform_notification';

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Build a relationship with a parent.
     *
     * @return belongs_to
     */
    public function activity(): belongs_to {
        return $this->belongs_to(activity::class, 'activity_id');
    }

    /**
     * Relationship with notification_recipient entities.
     *
     * @return has_many
     */
    public function recipients(): has_many {
        return $this->has_many(notification_recipient::class, 'notification_id');
    }

    /**
     * Cast value to boolean.
     *
     * @return bool
     */
    protected function get_active_attribute(): bool {
        return (bool) $this->get_attributes_raw()['active'];
    }

}
