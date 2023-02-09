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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\event;

defined('MOODLE_INTERNAL') || die();

class assignment_user_assigned_bulk extends assignment_user {

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
        $this->data['objecttable'] = 'totara_competency_assignments';
    }

    public static function create_from_assignment_users(int $assignment_id, int $competency_id, array $user_ids = [], ?string $assignment_type = null) {
        $data = [
            'objectid' => $assignment_id,
            'other' => [
                'user_ids' => $user_ids,
                'assignment_id' => $assignment_id,
                'competency_id' => $competency_id,
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
        return get_string('event_assignment_user_assigned', 'totara_competency');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'User got assigned to a competency';
    }
}
