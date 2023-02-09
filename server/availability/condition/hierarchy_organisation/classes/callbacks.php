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
 * @package availability_hierarchy_organisation
 */

namespace availability_hierarchy_organisation;

defined('MOODLE_INTERNAL') || die();

/**
 * Callbacks for the availability hierarchy organisation.
 */
class callbacks {

    /**
     * Callback to delete the condition if the Organisation is uses gets
     * deleted.
     *
     * @param \hierarchy_organisation\event\organisation_deleted $event Event data
     * @deprecated since Totara 11.20, 12.11, 13
     */
    public static function organisation_deleted(\hierarchy_organisation\event\organisation_deleted $event) {
        /*
         * Deleting an organisation should not remove the activity restrictions related to that organisation.
         * This behaviour could inadvertently allow people to access and complete activities that they should not.
         * As a result, this event observer has been removed and the functionality is no longer available.
         */
        debugging('The event observer '.__CLASS__.' is no longer used. Please remove this class from events.php.', DEBUG_DEVELOPER);
    }
}
