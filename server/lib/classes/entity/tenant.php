<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_tenant
 */

namespace core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

defined('MOODLE_INTERNAL') || die();

/**
 * Tenant entity
 *
 * @property int $id ID
 * @property string $name Tenant name
 * @property string $idnumber ID number
 * @property string $description Description
 * @property string $descriptionformat Description format
 * @property int $suspended Suspended
 * @property int $categoryid Category ID
 * @property int $cohortid Cohort ID
 * @property int $timecreated Time record created
 * @property int $usercreated Time user updated
 *
 * @property-read cohort $cohort
 */
class tenant extends entity {

    public const CREATED_TIMESTAMP = 'timecreated';

    public const TABLE = 'tenant';

    /**
     * Audience relation
     *
     * @return belongs_to
     */
    public function cohort(): belongs_to {
        return $this->belongs_to(cohort::class, 'cohortid', 'id');
    }
}
