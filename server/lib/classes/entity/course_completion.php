<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\entity\course as course_entity;
use core\entity\user as user_entity;

/**
 * Course completion entity
 *
 * @property-read int $id ID
 * @property int $userid User ID
 * @property int $course Course ID
 * @property int|null $organisationid Organisation ID
 * @property int|null $positionid Position ID
 * @property int $timeenrolled When user was enrolled
 * @property int $timestarted When progress in course started
 * @property int $timecompleted When user completed the course
 * @property int $reaggregate Should progress be re-aggregated
 * @property string $rpl Recognision of prior learning
 * @property float $rplgrade Grade achieved through prior learning
 * @property int $invalidatecache Deprecated since Totara 10, please do not use
 * @property int $status Progress status code
 * @property int $renewalstatus Renewal status code
 * @property int $duedate Calculated completion due date
 *
 * @property int $course_idcore_orm_entity_property_testcase
 * @property-read course_entity $course_instance
 *
 * @package core\entity
 */
class course_completion extends entity {

    public const TABLE = 'course_completions';

    /**
     * Get the user this completion record is for
     *
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user_entity::class, 'userid');
    }

    /**
     * Get the course instance this completion record is for
     *
     * @return belongs_to
     */
    public function course_instance(): belongs_to {
        return $this->belongs_to(course_entity::class, 'course');
    }

}
