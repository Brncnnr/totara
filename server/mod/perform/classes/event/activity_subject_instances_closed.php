<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

use core\event\base;
use mod_perform\models\activity\activity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;

/**_
 * Event is triggered when an activity's subject instances are closed in bulk.
 */
class activity_subject_instances_closed extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = subject_instance_entity::TABLE;
    }

    /**
     * Create instance of event.
     *
     * @param activity $activity the activity whose subject instances were closed.
     *
     * @return self|base
     */
    public static function create_from_activity(activity $activity): self {
        $data = [
            'objectid' => $activity->id,
            'userid' => \core\session\manager::get_realuser()->id,
            'other' => [],
            'context' => $activity->get_context()
        ];

        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_activity_subject_instances_closed', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        return "Subject instances in the activity with id '$this->objectid'"
             . " were closed in bulk by the user with id '$this->userid'";
    }

}
