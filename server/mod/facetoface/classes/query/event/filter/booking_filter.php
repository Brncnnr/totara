<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query\event\filter;

use core\orm\query\builder;
use mod_facetoface\query\event\filter_factory;
use mod_facetoface\signup\state\attendance_state;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\event_cancelled;
use mod_facetoface\signup\state\user_cancelled;
use mod_facetoface\signup\state\requested;
use mod_facetoface\signup\state\requestedadmin;
use mod_facetoface\signup\state\requestedrole;
use mod_facetoface\signup\state\waitlisted;

defined('MOODLE_INTERNAL') || die();

/**
 * Filter by booking status.
 */
final class booking_filter extends filter {
    const ALL = 0;
    const OPEN = 1;
    const BOOKED = 2;
    const WAITLISTED = 3;
    const REQUESTED = 4;
    const CANCELLED = 5;

    /**
     * @var int
     */
    private $value;

    /**
     * @var int
     */
    private $userid;

    /**
     * @param int $value    ALL, OPEN or BOOKED
     */
    public function __construct(int $value = 0) {
        parent::__construct('booking');
        $this->set_value($value);
    }

    /**
     * @param int $value    ALL, OPEN or BOOKED
     * @return booking_filter
     */
    public function set_value(int $value): booking_filter {
        $this->value = $value;
        return $this;
    }

    /**
     * @param integer $userid
     * @return booking_filter
     */
    public function set_userid(int $userid): booking_filter {
        $this->userid = $userid;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get_where_and_params(int $time): array {
        debugging('The method ' . __METHOD__ . '() has been deprecated and no longer effective. Please use the apply() counterpart instead.', DEBUG_DEVELOPER);
        return ["(1=1)", []];
    }

    public function apply(builder $builder, int $time): void {
        switch ($this->value) {
            case self::OPEN:
                filter_factory::event_upcoming($builder, $time);
                filter_factory::registration_open($builder, $time);
                filter_factory::booking_capacity($builder, '<', 'max');
                filter_factory::event_user_signup_available($builder, $this->userid);
                break;

            case self::BOOKED:
                filter_factory::event_not_cancelled($builder);
                filter_factory::event_user_signup_with($builder, $this->userid, attendance_state::get_all_attendance_code_with([booked::class]));
                break;

            case self::WAITLISTED:
                filter_factory::event_not_cancelled($builder);
                filter_factory::event_user_signup_with($builder, $this->userid, [waitlisted::get_code()]);
                break;

            case self::REQUESTED:
                filter_factory::event_not_cancelled($builder);
                filter_factory::event_user_signup_with($builder, $this->userid, [requested::get_code(), requestedadmin::get_code(), requestedrole::get_code()]);
                break;

            case self::CANCELLED:
                filter_factory::event_user_signup_with($builder, $this->userid, [user_cancelled::get_code()]);
                break;
        }
    }
}
