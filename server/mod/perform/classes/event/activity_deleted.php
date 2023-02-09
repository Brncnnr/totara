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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use mod_perform\models\activity\activity;

class activity_deleted extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'perform';
    }

    /**
     * Create event by perform activity.
     *
     * @param activity $activity
     * @return self|base
     */
    public static function create_from_activity(activity $activity): self {
        $data = [
            'objectid' => $activity->get_id(),
            'relateduserid' => null,
            'other' => [],
            'context' => $activity->get_context(),
        ];
        return static::create($data);
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_activity_deleted', 'mod_perform');
    }

    public function get_description() {
        return "The user with id '$this->userid' has deleted the performance activity with id '{$this->objectid}'.";
    }
}