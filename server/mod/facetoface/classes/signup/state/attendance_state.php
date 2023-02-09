<?php

/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\state;

use mod_facetoface\signup\transition;
use mod_facetoface\signup\condition\{
    event_is_cancelled,
    event_is_not_cancelled,
    event_taking_attendance,
    signup_not_archived
};

defined('MOODLE_INTERNAL') || die();

/**
 * This abstract class represents graded states.
 */
abstract class attendance_state extends state {

    /**
     * Get conditions and validations of transitions from current state
     * @see \mod_facetoface\signup\state\booked::get_transitions_to_attendance_states
     *
     * @return transition[]
     */
    final public function get_map(): array {
        $transitions = [];
        foreach (self::get_all_attendance_states() as $stateclass) {
            // do not include itself
            if (get_class($this) !== $stateclass) {
                $transitions[] = transition::to(new $stateclass($this->signup))->with_conditions(
                    signup_not_archived::class,
                    event_is_not_cancelled::class,
                    event_taking_attendance::class
                );
            }
        }
        // Attendance state can always be reverted back to booked as long as it is not archived.
        $transitions[] = transition::to(new booked($this->signup))->with_conditions(
            signup_not_archived::class
        );

        // Or attendance state is able to go to event_cancelled, only if the event is cancelled
        $transitions[] = transition::to(new event_cancelled($this->signup))->with_conditions(
            event_is_cancelled::class
        );

        return $transitions;
    }

    /**
     * Get all attendance state classes
     *
     * @return string[]
     */
    final public static function get_all_attendance_states(): array {
        $classes = \core_component::get_namespace_classes(
            'signup\state',
            self::class,
            'mod_facetoface'
        );
        return $classes;
    }

    /**
     * Get the code of all attendance state classes
     *
     * @return int[]
     */
    final public static function get_all_attendance_code(): array {
        return self::get_all_attendance_code_with([]);
    }

    /**
     * Get the code of all attendance state classes alongside additional states
     *
     * @param string[] $additional_states
     *
     * @return int[]
     */
    final public static function get_all_attendance_code_with(array $additional_states): array {
        $states = array_merge(self::get_all_attendance_states(), $additional_states);
        return array_map(
            function ($state) {
                if (!is_subclass_of($state, state::class)) {
                    throw new \coding_exception($state.' is not a subclass of state.');
                }

                return $state::get_code();
            },
            $states
        );
    }

    /**
     * Get the csv code of all attendance state classes
     *
     * @return int[]
     */
    final public static function get_all_attendance_csv(): array {
        $states = self::get_all_attendance_states();
        $status = [];
        foreach ($states as $state) {
            $status[$state::get_csv_code()] = $state;
        }
        return $status;
    }
}
