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
use mod_perform\event\participant_instance_progress_updated;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\subject_instance;

class participant_instance_progress {

    /**
     * When progress status of a participant instance is updated, make the subject instance check if it needs a
     * progress status update as well.
     *
     * @param base|participant_instance_progress_updated $event
     */
    public static function progress_updated(base $event) {
        /** @var participant_instance $participant_instance */
        $participant_instance = participant_instance::repository()->find_or_fail($event->objectid);
        $subject_instance_model = subject_instance::load_by_entity($participant_instance->subject_instance);
        $subject_instance_model->update_progress_status();
    }
}