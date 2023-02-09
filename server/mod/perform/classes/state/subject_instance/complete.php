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

namespace mod_perform\state\subject_instance;

use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\state\subject_instance\condition\all_participant_instances_complete;
use mod_perform\state\subject_instance\condition\at_least_one_participant_instance_started;
use mod_perform\state\subject_instance\condition\no_participant_instances_complete;
use mod_perform\state\subject_instance\condition\not_all_participant_instances_complete;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "complete" progress status of a subject instance.
 *
 * @package mod_perform
 */
class complete extends subject_instance_progress{

    public static function get_name(): string {
        return 'COMPLETE';
    }

    public static function get_code(): int {
        return 20;
    }

    public function get_transitions(): array {
        return [
            transition::to(new in_progress($this->object))->with_conditions([
                not_all_participant_instances_complete::class,
                at_least_one_participant_instance_started::class,
            ]),

            transition::to(new not_started($this->object))->with_conditions([
                no_participant_instances_complete::class,
            ]),

            // All participants have completed their instances.
            transition::to(new complete($this->object))->with_conditions([
                all_participant_instances_complete::class
            ]),
        ];
    }

    public function update_progress(): void {
        foreach ([in_progress::class, complete::class] as $to_state) {
            if ($this->can_switch($to_state)) {
                $this->object->switch_state($to_state);
                break;
            }
        }
    }

    public function on_enter(): void {
        /** @var subject_instance_entity $subject_instance_entity */
        $subject_instance_entity = subject_instance_entity::repository()->find($this->get_object()->get_id());
        // Only if not yet completed update the completed at timestamp
        if (empty($subject_instance_entity->completed_at)) {
            $subject_instance_entity->completed_at = time();
            $subject_instance_entity->update();
        }
    }

    public function manually_complete(): void {
        // Not relevant when already complete. Do nothing.
    }

    public function manually_uncomplete(): void {
        foreach ([in_progress::class, not_started::class] as $to_state) {
            if ($this->can_switch($to_state)) {
                $this->object->switch_state($to_state);
                break;
            }
        }
    }

}
