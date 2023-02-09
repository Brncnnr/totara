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

namespace mod_perform\event\helper;

use mod_perform\models\activity\track;

/**
 * Holds a track's scheduling details.
 */
class track_schedule {
    /**
     * @var track parent track.
     */
    private $parent = null;

    /**
     * @var track_schedule_start_date_dynamic|track_schedule_start_date_fixed
     *      subject instance generation schedule.
     */
    private $schedule = null;

    /**
     * @var track_schedule_due_date_dynamic|track_schedule_due_date_fixed track
     *      completion date.
     */
    private $due_date = null;

    /**
     * Default constructor.
     *
     * @param track $track parent track.
     */
    public function __construct(track $track) {
        $this->parent = $track;

        $this->schedule = $track->schedule_is_fixed
            ? new track_schedule_start_date_fixed(
                $track->get_schedule_fixed_from_setting(),
                $track->get_schedule_fixed_to_setting()
            )
            : new track_schedule_start_date_dynamic(
                $track->schedule_dynamic_source,
                $track->schedule_dynamic_from,
                $track->schedule_dynamic_to
            );

        if ($track->due_date_is_enabled) {
            $this->due_date = $track->due_date_fixed
                ? new track_schedule_due_date_fixed($track->get_due_date_fixed_setting())
                : new track_schedule_due_date_dynamic($track->due_date_offset);
        }
    }

    /**
     * Returns the parent track.
     *
     * @return track the parent track.
     */
    public function get_track(): track {
        return $this->parent;
    }

    /**
     * Returns the subject instance generation schedule.
     *
     * @return track_schedule_start_date_dynamic|track_schedule_start_date_fixed
     *         the schedule date or null if no schedule were set.
     */
    public function get_schedule() {
        return $this->schedule;
    }

    /**
     * Indicates whether the subject instance generation schedule is fixed.
     *
     * @return bool true if the subject instance generation schedule is fixed.
     */
    public function is_fixed(): bool {
        return $this->schedule instanceof track_schedule_start_date_fixed;
    }

    /**
     * Indicates whether there is a ending date to the subject instance generation
     * schedule.
     *
     * @return bool true if there is and ending date.
     */
    public function has_end_date(): bool {
        return empty($this->schedule->get_end_date());
    }

    /**
     * Returns the due date.
     *
     * @return track_schedule_due_date_dynamic|track_schedule_due_date_fixed the
     *         due date. This could also be null if no due date were set.
     */
    public function get_due_date() {
        return $this->due_date;
    }

    /**
     * Indicates whether the track completion date is fixed.
     *
     * @return bool true if the track completion is fixed.
     */
    public function is_due_date_fixed(): bool {
        return $this->due_date instanceof track_schedule_due_date_fixed;
    }
}
