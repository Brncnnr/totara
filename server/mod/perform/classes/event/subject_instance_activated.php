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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

use core\event\base;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\subject_instance;

/**
 * Class subject_instance_activated
 *
 * This event is fired when a subject instance is marked active.
 */
class subject_instance_activated extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = subject_instance_entity::TABLE;
    }

    /**
     * Create event from a subject instance.
     *
     * @param subject_instance $subject_instance
     * @return self|base
     */
    public static function create_from_subject_instance(subject_instance $subject_instance): self {
        $data = [
            'objectid' => $subject_instance->id,
            'relateduserid' => $subject_instance->subject_user_id,
            'other' => [],
            'context' => $subject_instance->get_context(),
        ];

        $event = static::create($data);
        $event->add_record_snapshot(subject_instance_entity::TABLE, $subject_instance->to_record());

        return $event;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_subject_instance_activated', 'mod_perform');
    }

}
