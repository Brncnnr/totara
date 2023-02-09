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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\entity;
use core\orm\entity\relations\has_one_through;

/**
 * Temporary track user assignment queue entity
 *
 * @property int $id
 *
 * @package mod_perform\entity
 */
class temp_track_user_assignment_queue extends entity {

    public const TABLE = 'perform_temp_tua_queue';

    public const FIELDS = [
        'id' => [XMLDB_TYPE_INTEGER, '10', null, true, true],
        'track_user_assignment_id' => [XMLDB_TYPE_INTEGER, '10'],
        'subject_instance_count' => [XMLDB_TYPE_INTEGER, '10'],
        'last_instance_progress' => [XMLDB_TYPE_INTEGER, '10'],
        'last_instance_availability' => [XMLDB_TYPE_INTEGER, '10'],
        'last_instance_created_at' => [XMLDB_TYPE_INTEGER, '10'],
        'last_instance_completed_at' => [XMLDB_TYPE_INTEGER, '10'],
        'last_instance_closed_at' => [XMLDB_TYPE_INTEGER, '10'],
        'track_repeating_is_enabled' => [XMLDB_TYPE_INTEGER, '10'],
        'track_repeating_trigger' => [XMLDB_TYPE_CHAR, '255'],
        'track_repeating_type' => [XMLDB_TYPE_INTEGER, '10'],
        'track_repeating_offset' => [XMLDB_TYPE_TEXT],
        'track_due_date_is_enabled' => [XMLDB_TYPE_INTEGER, '10'],
        'track_due_date_is_fixed' => [XMLDB_TYPE_INTEGER, '10'],
        'track_due_date_fixed' => [XMLDB_TYPE_INTEGER, '10'],
        'track_due_date_offset' => [XMLDB_TYPE_TEXT]
    ];

    public function activity(): has_one_through {
        return $this->has_one_through(
            track::class,
            activity::class,
            'track_id',
            'id',
            'activity_id',
            'id'
        );
    }
}