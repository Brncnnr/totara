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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use mod_perform\models\activity\participant_source;

abstract class progress_updated_event extends base {
    /**
     * Formulates the event's 'other' array values.
     *
     * @param string $current_progress_state
     * @param string|null $from_progress_state
     * @param bool $anonymise_responses
     * @param int $participant_source
     *
     * @return array the 'other' array values.
     */
    protected static function data_other(
        string $current_progress_state,
        ?string $from_progress_state = null,
        bool $anonymise_responses = false,
        int $participant_source = participant_source::INTERNAL
    ): array {
        return [
            'progress' => $current_progress_state,
            'previous_progress' => $from_progress_state,
            'anonymous' => $anonymise_responses,
            'participant_source' => $participant_source
        ];
    }

    /**
     * Get the description string for the progress state changing.
     *
     * @return string the progress description string.
     */
    protected function get_progress_change_description(): string {
        $current_progress = $this->other['progress'];
        $previous_progress = $this->other['previous_progress'];

        return $previous_progress
            ? "from $previous_progress to $current_progress"
            : "to $current_progress";
    }

    /**
     * Get the human readable name for the participant source.
     *
     * @return string the participant source string.
     */
    protected function get_participant_source(): string {
        return (int) $this->other['participant_source'] === participant_source::EXTERNAL
            ? 'external'
            : 'internal';
    }

    /**
     * Anonymise the specified user id if neccessary.
     *
     * @param int $user_id
     * @return string the anonymised user id
     */
    protected function get_anonymised_user(int $user_id): string {
        return $this->other['anonymous'] ? 'anonymous' : $user_id;
    }
}
