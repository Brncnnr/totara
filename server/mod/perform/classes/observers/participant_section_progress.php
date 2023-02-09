<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\base;
use mod_perform\event\participant_section_progress_updated;
use mod_perform\entity\activity\participant_section;
use mod_perform\models\activity\participant_instance;

class participant_section_progress {

    /**
     * When progress status of a participant section is updated
     *
     * @param base|participant_section_progress_updated $event
     */
    public static function progress_updated(base $event) {
        /** @var participant_section $participant_section */
        $participant_section = participant_section::repository()->find_or_fail($event->objectid);
        $participant_instance_model = participant_instance::load_by_entity($participant_section->participant_instance);
        $participant_instance_model->update_progress_status();
    }
}