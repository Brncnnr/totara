<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\notification;
defined('MOODLE_INTERNAL') || die();

use facetoface_notification;

class notification_map {
    /**
     * The hash map of condition key (integer) with another hasmap of condition (language identifier only) and recipient description
     *
     * @var array
     */
    private $map;

    /**
     * @var facetoface_notification
     */
    private $notification;

    /**
     * notification_definition constructor.
     * @param facetoface_notification $notification
     */
    public function __construct(facetoface_notification $notification) {
        $this->notification = $notification;
        $this->init();
    }

    /**
     * Building the notification maps, the map is about mapping notification type with itself condition and recipients.
     *
     * @return void
     */
    private function init(): void {
        if (!empty($this->map)) {
            return;
        }

        $time = $this->notification->scheduleamount;
        $unit = null;

        if (1 == $time) {
            $unit = get_string('schedule_unit_'.$this->notification->scheduleunit.'_singular', 'mod_facetoface');
        } else if (1 < $time) {
            $unit = get_string('schedule_unit_'.$this->notification->scheduleunit, 'mod_facetoface', $time);
        }

        $this->map = [
            MDL_F2F_CONDITION_BEFORE_SESSION => [
                'condition' => get_string("occursxbeforesession", 'facetoface', $unit),
                'recipients' => []
            ],

            MDL_F2F_CONDITION_AFTER_SESSION => [
                'condition' => get_string("occursxaftersession", 'facetoface', $unit),
                'recipients' => []
            ],

            MDL_F2F_CONDITION_BOOKING_CONFIRMATION => [
                'condition' => "occurswhenuserbookssession",
                'recipients' => [
                    get_string('learner')
                ]
            ],

            MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION => [
                'condition' => get_string("occurswhenusersbookingiscancelled", 'facetoface'),
                'recipients' => [
                    get_string('status_user_cancelled', 'facetoface')
                ]
            ],

            MDL_F2F_CONDITION_DECLINE_CONFIRMATION => [
                'condition' => get_string("occurswhenuserrequestssessionwithmanagerdecline", 'facetoface'),
                'recipients' => [
                    get_string('status_pending_requests', 'facetoface')
                ]
            ],

            MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION => [
                'condition' => get_string("occurswhenuserwaitlistssession", 'facetoface'),
                'recipients' => [
                    get_string('status_waitlisted', 'facetoface')
                ]
            ],

            MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER => [
                'condition' => get_string("occurswhenuserrequestssessionwithmanagerapproval", 'facetoface'),
                'recipients' => [
                    get_string('manager', 'role')
                ]
            ],

            MDL_F2F_CONDITION_TRAINER_CONFIRMATION => [
                'condition' => get_string("occurswhenseminareventtrainerassigned", 'facetoface'),
                'recipients' => [
                    get_string('trainer')
                ]
            ],

            MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION => [
                'condition' => get_string("occurswhenseminareventcancel", 'facetoface'),
                'recipients' => [
                    get_string('trainer')
                ]
            ],

            MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT => [
                'condition' => get_string("occurswhenseminareventtrainerunassigned", 'facetoface'),
                'recipients' => [
                    get_string('trainer')
                ]
            ],

            MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED => [
                'condition' => get_string("occurswhenseminareventsignupexpired", 'facetoface'),
                'recipients' => [
                    get_string('learner')
                ]
            ],

            MDL_F2F_CONDITION_SESSION_CANCELLATION => [
                'condition' => get_string("occurswhenseminareventcancel", 'facetoface'),
                'recipients' => [
                    get_string('status_user_cancelled', 'facetoface')
                ]
            ],

            MDL_F2F_CONDITION_RESERVATION_CANCELLED => [
                'condition' => "",
                'recipients' => [
                    get_string('status_user_cancelled', 'facetoface')
                ]
            ],

            MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED => [
                'condition' => "",
                'recipients' => [
                    get_string('status_user_cancelled', 'facetoface')
                ]
            ],

            MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE => [
                'condition' => get_string("occurswhenuserrequestssessionwithmanagerapproval", 'facetoface'),
                'recipients' => [
                    get_string('sessionroles', 'facetoface')
                ]
            ],

            MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN => [
                'condition' => "",
                'recipients' => [
                    get_string('admin')
                ]
            ],

            MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS => [
                'condition' => "",
                'recipients' => [
                    get_string('learner')
                ]
            ]
        ];
    }

    /**
     * @return string
     */
    public function get_condition_description(): string {
        $conditiontype = $this->notification->conditiontype;

        if (null !== $conditiontype && isset($this->map[$conditiontype])) {
            return $this->map[$conditiontype]['condition'];
        }

        return "";
    }

    /**
     * @return string[]
     */
    public function get_recipients(): array {
        $conditiontype = $this->notification->conditiontype;

        if (null !== $conditiontype && isset($this->map[$conditiontype])) {
            return $this->map[$conditiontype]['recipients'];
        }

        return [];
    }
}