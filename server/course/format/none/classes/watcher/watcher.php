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
 * @package format_none
 */
namespace format_none\watcher;

use core_course\hook\format\legacy_course_format_supported;

class watcher {
    /**
     * Removing the format standard from the list of formats that are meant for the legacy course.
     *
     * @param legacy_course_format_supported $hook
     * @return void
     */
    public static function watch_legacy_course_get_formats(legacy_course_format_supported $hook): void {
        $hook->remove_format('none');
    }
}