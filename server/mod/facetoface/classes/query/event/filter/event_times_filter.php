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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query\event\filter;

use core\orm\query\builder;
use mod_facetoface\event_time;
use mod_facetoface\query\event\filter_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Filtering event base on the type of time, whether event is UPCOMING, IN-PROGRESS or OVER. This filter will help to build
 * the part of SQL for that checking of criteria.
 */
final class event_times_filter extends filter {
    /**
     * @var int[]
     */
    private $eventtimes;

    /**
     * @var integer
     */
    private static $index = 0;

    /**
     * event_time_filter constructor.
     *
     * @param int[] $values    The constants defined in class \mod_facetoface\event_time
     */
    public function __construct(array $values) {
        parent::__construct('event_times');
        $this->set_eventtimes($values);
    }

    /**
     * $value is one or more values defined in event_time's constants.
     * @param int[] $values
     *
     * @return event_time_filter
     */
    public function set_eventtimes(array $values): event_times_filter {
        $values = array_unique($values);
        if (count($values) === 0 || in_array(event_time::ALL, $values)) {
            $this->eventtimes = [];
        } else {
            $this->eventtimes = $values;
        }
        return $this;
    }

    /**
     * @return string
     */
    private static function new_param(): string {
        $i = ++self::$index;
        return "timenow{$i}_etf";
    }

    /**
     * @inheritDoc
     */
    public function get_where_and_params(int $time): array {
        debugging('The method ' . __METHOD__ . '() has been deprecated and no longer effective. Please use the apply() counterpart instead.', DEBUG_DEVELOPER);
        return ["(1=1)", []];
    }

    /**
     * @inheritDoc
     */
    public function apply(builder $builder, int $time): void {
        if (empty($this->eventtimes) || (count($this->eventtimes) === 1 && $this->eventtimes[0] == event_time::ALL)) {
            return;
        }

        $builder->where(function (builder $outer) use ($time) {
            foreach ($this->eventtimes as $eventtime) {
                switch ($eventtime) {
                    case event_time::UPCOMING:
                        $outer->or_where(function (builder $inner) use ($time) {
                            filter_factory::event_upcoming($inner, $time);
                        });
                        break;

                    case event_time::OVER:
                        $outer->or_where(function (builder $inner) use ($time) {
                            filter_factory::event_over($inner, $time);
                        });
                        break;

                    case event_time::FUTURE:
                        $outer->or_where(function (builder $inner) use ($time) {
                            filter_factory::event_future($inner, $time);
                        });
                        break;

                    case event_time::INPROGRESS:
                        $outer->or_where(function (builder $inner) use ($time) {
                            filter_factory::event_inprogress($inner, $time);
                        });
                        break;

                    case event_time::PAST:
                        $outer->or_where(function (builder $inner) use ($time) {
                            filter_factory::event_past($inner, $time);
                        });
                        break;

                    case event_time::WAITLISTED:
                        $outer->or_where(function (builder $inner) {
                            filter_factory::event_waitlisted($inner);
                        });
                        break;

                    case event_time::CANCELLED:
                        $outer->or_where(function (builder $inner) {
                            filter_factory::event_cancelled($inner);
                        });
                        break;
                }
            }
        });
    }
}
