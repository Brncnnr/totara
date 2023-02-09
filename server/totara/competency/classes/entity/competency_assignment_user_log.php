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


use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * @property int $assignment_id
 * @property int $user_id
 * @property int $action
 * @property int $created_at
 *
 * @property-read string $action_name
 * @property-read assignment $assignment
 *
 * @package totara_competency\entity
 */
class competency_assignment_user_log extends entity {

    public const TABLE = 'totara_competency_assignment_user_logs';

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = '';
    public const SET_UPDATED_WHEN_CREATED = false;

    protected $update_timestamps = true;

    protected $extra_attributes = [
        'action_name'
    ];

    public const ACTION_ASSIGNED = 1;
    public const ACTION_UNASSIGNED_USER_GROUP = 2;
    public const ACTION_UNASSIGNED_ARCHIVED = 3;
    public const ACTION_TRACKING_START = 4;
    public const ACTION_TRACKING_END = 5;

    public const ACTION_ASSIGNED_NAME = 'assigned';
    public const ACTION_UNASSIGNED_USER_GROUP_NAME = 'unassigned_usergroup';
    public const ACTION_UNASSIGNED_ARCHIVED_NAME = 'unassigned_archived';
    public const ACTION_TRACKING_START_NAME = 'tracking_started';
    public const ACTION_TRACKING_END_NAME = 'tracking_ended';

    /**
     * Returns a textual representation for the action number
     *
     * @return string
     */
    protected function get_action_name_attribute(): string {
        switch ($this->action) {
            case self::ACTION_ASSIGNED:
                $name = self::ACTION_ASSIGNED_NAME;
                break;
            case self::ACTION_UNASSIGNED_USER_GROUP:
                $name = self::ACTION_UNASSIGNED_USER_GROUP_NAME;
                break;
            case self::ACTION_UNASSIGNED_ARCHIVED:
                $name = self::ACTION_UNASSIGNED_ARCHIVED_NAME;
                break;
            case self::ACTION_TRACKING_START:
                $name = self::ACTION_TRACKING_START_NAME;
                break;
            case self::ACTION_TRACKING_END:
                $name = self::ACTION_TRACKING_END_NAME;
                break;
            default:
                throw new \coding_exception('Unknown action name for assignment user log action \''.$this->action.'\'');
        }
        return $name;
    }

    /**
     * Assignment
     *
     * @return belongs_to
     */
    public function assignment(): belongs_to {
        return $this->belongs_to(assignment::class, 'assignment_id');
    }

}
