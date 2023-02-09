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

use totara_competency\entity\competency_assignment_user;

defined('MOODLE_INTERNAL') || die();

class assignment_user_archived extends assignment_user {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'totara_competency_assignment_users';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_assignment_user_archived', 'totara_competency');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return 'Users assignment got archived to a competency';
    }

    /**
     * Create instance of event with tracking data
     *
     * @param competency_assignment_user $assignment_user
     * @param string $assignment_type
     * @param bool $tracking_continues
     * @return self
     */
    public static function create_from_assignment_user_with_tracking(
        competency_assignment_user $assignment_user,
        ?string $assignment_type = null,
        bool $tracking_continues = false
    ) {
        $assignment_user = (object) $assignment_user->get_attributes_raw();

        $data = [
            'objectid' => $assignment_user->assignment_id,
            'relateduserid' => $assignment_user->user_id,
            'other' => [
                'assignment_id' => $assignment_user->assignment_id,
                'competency_id' => $assignment_user->competency_id,
                'type' => $assignment_type ?? null,
                'tracking_continues' => $tracking_continues,
            ],
            'context' => \context_system::instance()
        ];
        return static::create($data);
    }

    /**
     * Return related tracking information
     *
     * @return bool
     */
    public function get_tracking_continues(): bool {
        return $this->data['other']['tracking_continues'] ?? false;
    }

}
