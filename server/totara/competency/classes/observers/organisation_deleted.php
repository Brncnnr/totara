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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\observers;

use hierarchy_organisation\event;
use totara_competency\models\assignment_actions;
use totara_competency\user_groups;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer
 */
class organisation_deleted {

    public static function observe(event\organisation_deleted $event) {
        $id = $event->get_data()['objectid'];

        // If the id is empty, it means that something went horribly wrong.
        if (!empty($id)) {
            assignment_actions::create()->archive_for_user_group(user_groups::ORGANISATION, $id);
        }
    }

}
