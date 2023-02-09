<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

use mod_perform\models\activity\track_assignment;

defined('MOODLE_INTERNAL') || die();

class track_user_assigned_bulk extends track_user_assignment {

    protected $user_ids = [];

    protected $is_bulk = true;

    public function get_user_id(): ?int {
        return null;
    }

    public function get_user_ids(): array {
        return $this->other['user_ids'];
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'perform_track_user_assignment';
    }

    /**
     * Create an instance of this event.
     *
     * @param track_assignment $track_assignment track the users are assigned to.
     * @param array $subject_user_ids users assigned to the track.
     *
     * @return track_user_assigned_bulk the event.
     */
    public static function create_from_track_assignment(
        track_assignment $track_assignment,
        array $subject_user_ids = []
    ) {
        $data = [
            'objectid' => $track_assignment->track_id,
            'other' => [
                'user_ids' => $subject_user_ids,
                'type' => $track_assignment->type,
            ],
            'context' => $track_assignment->track->activity->get_context()
        ];

        return static::create($data);
    }

    /**
     * Use create_from_track_assignment() instead.
     *
     * @deprecated since Totara 13.1.
     * @param int $track_id
     * @param array $subject_user_ids
     * @param string|null $assignment_type
     * @return \core\event\base
     */
    public static function create_from_user_assignments(
        int $track_id,
        array $subject_user_ids = [],
        ?string $assignment_type = null
    ) {
        debugging(
            'track_user_assigned_bulk::create_from_user_assignments has been deprecated. Use create_from_track_assignment() instead',
            DEBUG_DEVELOPER
        );

        $data = [
            'objectid' => $track_id,
            'other' => [
                'user_ids' => $subject_user_ids,
                'type' => $assignment_type ?? null,
            ],
            'context' => \context_system::instance()
        ];
        return static::create($data);
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_track_users_assigned', 'mod_perform');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'Users have been assigned to a track';
    }

}
