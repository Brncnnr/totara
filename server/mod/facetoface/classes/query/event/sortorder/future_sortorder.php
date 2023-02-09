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

namespace mod_facetoface\query\event\sortorder;

use core\orm\query\builder;
use mod_facetoface\query\event\filter_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Sort events in order:
 * + waitlisted
 * + upcoming (far away future first, then nearest)
 * + current (in progress event)
 * + past (recently over event to the very far past over event)
 * + cancelled (waitlisted cancel first then the cancelled event with furthest timestart)
 */
final class future_sortorder extends sortorder {
    /**
     * @return string
     * @inheritdoc
     */
    public function get_sort_sql(): string {
        debugging('The method ' . __METHOD__ . '() has been deprecated and no longer effective. Please use the apply() counterpart instead.', DEBUG_DEVELOPER);
        return "";
    }

    public function apply(builder $builder): void {
        // PostgreSQL and MySQL sort NULL in a different order. We need wait-listed events to be the furthest future
        // events, meaning NULL needs to act as a positive maximum value. So we use PHP_INT_MAX as the
        // timestart/timefinish for events that are waitlisted (whose actual timestart/finish is NULL)
        $max = PHP_INT_MAX;
        $builder->reset_order_by()
                ->order_by('s.cancelledstatus', 'asc')
                ->order_by_raw("COALESCE(m.maxtimefinish, {$max}) DESC")
                ->order_by_raw("COALESCE(m.mintimestart, {$max}) DESC")
                ->order_by('s.id', 'desc');
    }
}
