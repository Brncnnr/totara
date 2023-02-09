<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package criteria_linkedcourses
 */

namespace criteria_linkedcourses\observer;

use criteria_linkedcourses\items_processor;
use totara_competency\event\linked_courses_updated;

class linked_courses {

    public static function linked_courses_updated(linked_courses_updated $event) {
        $competency_id = $event->get_data()['objectid'];

        items_processor::update_items($competency_id);
    }

}
