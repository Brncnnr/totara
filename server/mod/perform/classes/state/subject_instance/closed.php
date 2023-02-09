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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\subject_instance;

use core\event\base;
use mod_perform\event\subject_instance_availability_closed;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\state_event;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "closed" availability status of a subject instance.
 *
 * @package mod_perform
 */
class closed extends subject_instance_availability implements state_event {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return 'CLOSED';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('subject_instance_availability_closed', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function get_transitions(): array {
        return [
            transition::to(new open($this->object)),
        ];
    }

    /**
     * @inheritDoc
     */
    public function close(): void {
        // Already in the correct state.
    }

    /**
     * @inheritDoc
     */
    public function open(): void {
        $this->object->switch_state(open::class);
    }

    /**
     * @inheritDoc
     */
    public function get_event(): base {
        /** @var subject_instance $subject_instance */
        $subject_instance = $this->get_object();
        return subject_instance_availability_closed::create_from_subject_instance($subject_instance);
    }

    /**
     * @inheritDoc
     */
    public function on_enter(): void {
        $subject_instance_entity = subject_instance_entity::repository()
            ->find($this->get_object()->get_id());

        if (is_null($subject_instance_entity->closed_at)) {
            // This assumes that the subject instance open state makes the value
            // null. If this value is not null, it implies that the state didn't
            // go from open to close and the time should not be updated.
            $subject_instance_entity->closed_at = time();
            $subject_instance_entity->update();
        }
    }
}
