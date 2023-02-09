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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Entity for table "user_enrolments"
 *
 * @property int        $id
 * @property int        $status
 * @property int        $enrolid
 * @property int        $userid
 * @property int|null   $timestart
 * @property int|null   $timeend
 * @property int        $modifierid
 * @property int        $timecreated
 * @property int        $timemodified
 * @property-read enrol $enrol_instance
 * @property-read int   $time_enrolled
 */
final class user_enrolment extends entity {
    /**
     * @var string
     */
    public const TABLE = 'user_enrolments';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    protected $extra_attributes = [
        'time_enrolled'
    ];

    /**
     * @return bool
     */
    public function is_active(): bool {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $status = $this->get_attribute('status');
        return ENROL_USER_ACTIVE === (int) $status;
    }

    /**
     * @return bool
     */
    public function is_suspended(): bool {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $status = $this->get_attribute('status');
        return ENROL_USER_SUSPENDED === (int) $status;
    }

    protected function get_time_enrolled_attribute() {
        return $this->timestart ?: $this->timemodified;
    }

    /**
     * Get enrol instance
     *
     * @return belongs_to
     */
    public function enrol_instance(): belongs_to {
        return $this->belongs_to(enrol::class, 'enrolid', 'id');
    }
}