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
use mod_perform\entity\activity\track as track_entity;
use mod_perform\models\activity\track;

/**
 * Class track_subject_instance_generation_changed event is triggered when a user
 * changes the job assignment based subject instance generation setting.
 *
 * @package mod_perform\event
 */
class track_subject_instance_generation_changed extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = track_entity::TABLE;
    }

    /**
     * Create instance of event.
     *
     * @param track $track the changed track.
     *
     * @return self|base
     */
    public static function create_from_track(track $track): self {
        $data = [
            'objectid' => $track->id,
            'other' => [
                'is_per_job' => $track->is_per_job_subject_instance_generation()
            ],
            'context' => $track->activity->get_context()
        ];

        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_track_subject_instance_generation_changed', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $per_job = 'one subject instance per job assignment';
        $per_user = 'one subject instance per user';
        [$old_method, $new_method] = $this->other['is_per_job']
            ? [$per_user, $per_job]
            : [$per_job, $per_user];

        return "The subject generation method for the track with id '$this->objectid'"
             . " was changed by the user with id '$this->userid';"
             . " was originally $old_method, now is $new_method";
    }
}
