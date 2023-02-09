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

defined('MOODLE_INTERNAL') || die();

/**
 * Sort events in order:
 * + cancelled (cancelled event with recently timestart first -> furthiest then waitlisted cancel)
 * + past (far past over to recently over event)
 * + current (in progress event)
 * + upcoming (nearest first, then far away future)
 * + waitlisted
 */
final class default_sortorder extends sortorder {
    /** @var past_sortorder */
    private $order;

    /**
     * Constructor
     */
    public function __construct() {
        debugging('default_sortorder is deprecated. Please use future_sortorder or past_sortorder instead.', DEBUG_DEVELOPER);
        $this->order = new past_sortorder();
    }

    /**
     * @return string
     * @inheritdoc
     */
    public function get_sort_sql(): string {
        debugging('The method ' . __METHOD__ . '() has been deprecated and no longer effective. Please use the apply() counterpart instead.', DEBUG_DEVELOPER);
        return "";
    }

    public function apply(builder $builder): void {
        $this->order->sorter($builder);
    }
}