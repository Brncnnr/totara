<?php
/*
 * This file is part of Totara Perform
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform\rb\display;

use moodle_url;
use stdClass;

class participant_count_manage_participation extends participant_count {

    /**
     * @inheritDoc
     */
    protected static function get_url(stdClass $extrafields): moodle_url {
        return new moodle_url(
            '/mod/perform/manage/participation/participant_instances.php',
            ['activity_id' => $extrafields->activity_id, 'subject_instance_id' => $extrafields->subject_instance_id]
        );
    }

}
