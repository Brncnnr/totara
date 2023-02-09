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

namespace mod_perform\state\participant_instance;

use core\event\base;
use mod_perform\event\participant_instance_progress_updated;
use mod_perform\models\activity\participant_instance;
use mod_perform\state\state;
use mod_perform\state\state_event;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class representing a progress status of a participant instance.
 *
 * @package mod_perform
 */
abstract class participant_instance_progress extends state implements state_event {

    /**
     * Something has happened that may affect the instance's progress status, so check if we should switch.
     */
    abstract public function update_progress(): void;

    /**
     * Manually mark a participant instance as being complete
     */
    abstract public function manually_complete(): void;

    /**
     * Manually mark a participant instance as being not complete
     */
    abstract public function manually_uncomplete(): void;

    /**
     * @inheritDoc
     */
    public static function get_type(): string {
        return 'progress';
    }

    public static function get_display_name(): string {
        return get_string('participant_instance_status_' . strtolower(static::get_name()), 'mod_perform');
    }

    public function get_event(): base {
        /** @var participant_instance $participant_instance */
        $participant_instance = $this->get_object();
        $previous_state = $this->previous_state;

        return participant_instance_progress_updated::create_from_participant_instance(
            $participant_instance,
            $previous_state ? $previous_state::get_name() : null
        );
    }

}
