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

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_core\entity\relationship;

/**
 * Notification recipient entity
 *
 * Properties:
 * @property-read integer $id ID
 * @property boolean $active is active?
 * @property integer $notification_id parent notification record id
 * @property integer $core_relationship_id parent relationship record id
 *
 * Relationships:
 * @property-read notification $notification
 * @property-read relationship $relationship
 *
 * @package mod_perform\entity
 */
class notification_recipient extends entity {
    public const TABLE = 'perform_notification_recipient';

    /**
     * Build a relationship with a parent.
     *
     * @return belongs_to
     */
    public function notification(): belongs_to {
        return $this->belongs_to(notification::class, 'notification_id');
    }

    /**
     * Build a relationship with a parent.
     *
     * @return belongs_to
     */
    public function relationship(): belongs_to {
        return $this->belongs_to(relationship::class, 'core_relationship_id');
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
