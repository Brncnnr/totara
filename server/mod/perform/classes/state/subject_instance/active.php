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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\subject_instance;

use core\event\base;
use mod_perform\event\subject_instance_activated;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\state_event;

/**
 * This class represents the "active" manual status of a subject instance.
 *
 * @package mod_perform
 */
class active extends subject_instance_manual_status implements state_event {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return 'ACTIVE';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('subject_instance_status_active', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function get_transitions(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function get_event(): base {
        /** @var subject_instance $subject_instance */
        $subject_instance = $this->get_object();
        return subject_instance_activated::create_from_subject_instance($subject_instance);
    }

}
