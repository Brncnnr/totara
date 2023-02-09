<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_hierarchy_position
 */

namespace availability_hierarchy_position;

defined('MOODLE_INTERNAL') || die();

/**
 * Callbacks for the availability hierarchy position.
 */
class callbacks {

    /**
     * Callback to delete the condition if the position is uses gets
     * deleted.
     *
     * @param \hierarchy_position\event\position_deleted $event Event data
     * @deprecated since Totara 11.20, 12.11, 13
     */
    public static function position_deleted(\hierarchy_position\event\position_deleted $event) {
        /*
         * Deleting a position should not remove the activity restrictions related to that position.
         * This behaviour could inadvertently allow people to access and complete activities that they should not.
         * As a result, this event observer has been removed and the functionality is no longer available.
         */
        debugging('The event observer '.__CLASS__.' is no longer used. Please remove this class from events.php.', DEBUG_DEVELOPER);
    }
}
