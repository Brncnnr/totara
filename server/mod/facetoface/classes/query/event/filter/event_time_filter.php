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
 * @package mod_facetoface
 */

namespace mod_facetoface\query\event\filter;

use core\orm\query\builder;
use mod_facetoface\event_time;

defined('MOODLE_INTERNAL') || die();

/**
 * Filtering event base on the type of time, whether event is UPCOMING, IN-PROGRESS or OVER. This filter will help to build
 * the part of SQL for that checking of criteria.
 */
final class event_time_filter extends filter {
    /**
     * @var event_times_filter
     */
    private $filter;

    /**
     * event_time_filter constructor.
     *
     * @param int $eventtime    The constants defined in class \mod_facetoface\event_time
     */
    public function __construct(int $eventtime = event_time::ALL) {
        parent::__construct('event_time');
        $this->filter = new event_times_filter([$eventtime]);
    }

    /**
     * $value is one value defined in event_time's constants.
     * @param int $value
     *
     * @return event_time_filter
     */
    public function set_eventtime(int $value): event_time_filter {
        $this->filter->set_eventtimes([$value]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get_where_and_params(int $time): array {
        debugging('The method ' . __METHOD__ . '() has been deprecated and no longer effective. Please use the apply() counterpart instead.', DEBUG_DEVELOPER);

        return $this->filter->get_where_and_params($time);
    }

    public function apply(builder $builder, int $time): void {
        $this->filter->apply($builder, $time);
    }
}
