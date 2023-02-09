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

namespace mod_perform\event;

use core\event\base;
use mod_perform\entity\activity\track_assignment as track_assignment_entity;
use mod_perform\models\activity\track_assignment;
use mod_perform\user_groups\grouping;

/**_
 * Class track_user_group_assigned event is triggered when a user group is added
 * to an activity track.
 *
 * @package mod_perform\event
 */
class track_user_group_assigned extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = track_assignment_entity::TABLE;
    }

    /**
     * Create instance of event.
     *
     * @param track_assignment $assignment
     *
     * @return self|base
     */
    public static function create_from_track_assignment(track_assignment $assignment): self {
        $user_group = $assignment->group;

        $data = [
            'objectid' => $user_group->get_id(),
            'userid' => \core\session\manager::get_realuser()->id,
            'other' => [
                'track_id' => $assignment->track_id,
                'user_group' => $user_group->get_type()
            ],
            'context' => $assignment->track->activity->get_context()
        ];

        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_track_user_group_assigned', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $track_id = $this->other['track_id'];
        $type = grouping::get_type_name($this->other['user_group']);

        return "A $type with id '$this->objectid'"
             . " was assigned to the track with id '$track_id'"
             . " by the user with id '$this->userid'";
    }

}
