<?php
/**
 * This file is part of Totara LMS
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralms.com>
 * @package totara_certification
 */

namespace totara_certification\event;
use core\event\base;
use totara_notification\event\notifiable_event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user failed to re-certify.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * }
 *
 * @package totara_certification
 */
final class failure_to_recertify extends base implements notifiable_event {

    /**
     * Create instance of event.
     *
     * @param int $user_id
     * @param int $program_id
     *
     * @return base
     */
    public static function create_from_user_program(int $user_id, int $program_id): base {
        $data = array(
            'userid' => $user_id,
            'objectid' => $program_id,
            'context' => \context_program::instance($program_id),
        );

        return self::create($data);
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'certif';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @inheritDoc
     */
    public function get_notification_event_data(): array {
        return [
            "program_id" => $this->data['objectid'],
            "user_id" => $this->data['userid'],
        ];
    }

}
