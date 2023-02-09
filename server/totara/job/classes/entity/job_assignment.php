<?php
/*
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\entity;

use core\orm\entity\entity;

/**
 * Class job_assignment
 *
 * @property-read int $id
 * @property int $userid
 * @property string $fullname
 * @property string $shortname
 * @property string $idnumber
 * @property string $description
 * @property int $startdate
 * @property int $enddate
 * @property int $timecreated
 * @property int $timemodified
 * @property int $usermodified
 * @property int $positionid
 * @property int $positionassignmentdate
 * @property int $organisationid
 * @property int $managerjaid
 * @property string $managerjapath
 * @property int $tempmanagerjaid
 * @property int $tempmanagerexpirydate
 * @property int $appraiserid
 * @property int $sortorder
 * @property int $totarasync
 * @property int $synctimemodified
 *
 * @package totara_job\entity
 */
class job_assignment extends entity {

    public const TABLE = 'job_assignment';

    public const CREATED_TIMESTAMP = 'timecreated';

    public const UPDATED_TIMESTAMP = 'timemodified';

    public const SET_UPDATED_WHEN_CREATED = true;

}
