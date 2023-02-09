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

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use mod_perform\entity\activity\track_user_assignment as track_user_assignment_entity;

abstract class track_user_assignment extends base {

    protected $is_bulk = false;

    /**
     * Return related assignment id
     *
     * @return int|null
     */
    public function get_track_id(): ?int {
        return $this->data['other']['track_id'] ?? null;
    }

    /**
     * Return related assignment id
     *
     * @return int|null
     */
    public function get_user_id(): ?int {
        return $this->data['relateduserid'] ?? null;
    }

    public function is_bulk(): bool {
        return $this->is_bulk;
    }

    /**
     * Create instance of event.
     *
     * @param array $user_assignment
     * @param string|null $assignment_type
     * @return self|base
     */
    public static function create_from_array(array $user_assignment, ?string $assignment_type = null) {
        $user_assignment = (object) $user_assignment;

        $data = [
            'objectid' => $user_assignment->track_id,
            'relateduserid' => $user_assignment->subject_user_id,
            'other' => [
                'type' => $assignment_type ?? null,
            ],
            'context' => \context_system::instance()
        ];
        return static::create($data);
    }

    /**
     * Create instance of event.
     *
     * @param track_user_assignment_entity $user_assignment
     * @param string|null $assignment_type
     * @return self
     */
    public static function create_from_user_assignment(track_user_assignment_entity $user_assignment, ?string $assignment_type = null) {
        return self::create_from_array($user_assignment->get_attributes_raw(), $assignment_type);
    }

}
