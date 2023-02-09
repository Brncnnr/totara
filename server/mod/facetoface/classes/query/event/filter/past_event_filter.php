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
 * Filter past events by time period.
 */
final class past_event_filter extends filter {
    /**
     * @var int
     */
    private $timeperiod;

    /**
     * @param integer $timeperiod   The time period in days. 0 or a negative value disables this filter.
     */
    public function __construct(int $timeperiod = 0) {
        parent::__construct('past_event');
        $this->set_value($timeperiod);
    }

    /**
     * @param integer $timeperiod   The time period in days. 0 or a negative value disables this filter.
     * @return past_event_filter
     */
    public function set_value(int $timeperiod = 0): past_event_filter {
        $this->timeperiod = $timeperiod;
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
        if ($this->timeperiod > 0) {
            $builder->where(function (builder $outer) use ($time) {
                $outer->where('s.cancelledstatus', '=', 0)
                    ->where(function (builder $inner) use ($time) {
                        $timefrom = $time - ($this->timeperiod * DAYSECS);
                        $inner->or_where(function (builder $leaf) {
                            $leaf->where_null('m.mintimestart')->where_null('m.maxtimefinish', true);
                        })
                        ->or_where('m.mintimestart', '>', $timefrom)
                        ->or_where('m.maxtimefinish', '>', $time);
                    });
            });
        }
    }
}
