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

defined('MOODLE_INTERNAL') || die();

/**
 * Filter by attendance status.
 */
final class advanced_filter extends filter {
    const ALL = 0;
    const ATTENDANCE_OPEN = 1;
    const ATTENDANCE_SAVED = 2;
    const OVERBOOKED = 3;
    const UNDERBOOKED = 4;

    /**
     * @var int
     */
    private $value;

    /**
     * @var int
     */
    private $userid;

    /**
     * @param int $value    ALL, OPEN or SAVED
     */
    public function __construct(int $value = 0) {
        parent::__construct('attendance');
        $this->set_value($value);
    }

    /**
     * @param int $value    ALL, OPEN or SAVED
     * @return advanced_filter
     */
    public function set_value(int $value): advanced_filter {
        $this->value = $value;
        return $this;
    }

    /**
     * @param integer $userid
     * @return advanced_filter
     */
    public function set_userid(int $userid): advanced_filter {
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
            case self::ATTENDANCE_OPEN:
                // no break

            case self::ATTENDANCE_SAVED:
                filter_factory::event_not_cancelled($builder);
                filter_factory::event_booked($builder);
                $builder->where(function (builder $mediator) use ($time) {
                    $mediator->where(function (builder $inner) use ($time) {
                        filter_factory::event_attendance($inner, $time, $this->value == self::ATTENDANCE_SAVED);
                    })->or_where(function (builder $inner) use ($time) {
                        filter_factory::session_attendance($inner, $time, $this->value == self::ATTENDANCE_SAVED);
                    });
                });
                break;

            case self::OVERBOOKED:
                filter_factory::event_not_past_or_cancelled($builder, $time);
                filter_factory::booking_capacity($builder, '>', 'max');
                break;

            case self::UNDERBOOKED:
                filter_factory::event_not_past_or_cancelled($builder, $time);
                filter_factory::booking_capacity($builder, '<', 'min');
                break;
        }
    }
}
