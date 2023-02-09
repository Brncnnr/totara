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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\union;

use coding_exception;
use core\webapi\union_resolver;
use GraphQL\Type\Definition\ResolveInfo;
use core\entity\cohort;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use core\webapi\resolver\type\cohort as cohort_resolver;
use totara_hierarchy\webapi\resolver\type\organisation as organisation_resolver;
use totara_hierarchy\webapi\resolver\type\position as position_resolver;

/**
 * Assigned_to workflow union
 */
class workflow_assigned_to_union implements union_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve_type($objectvalue, $context, ResolveInfo $info): string {
        switch ($objectvalue) {
            case $objectvalue instanceof organisation:
                return organisation_resolver::class;
            case $objectvalue instanceof cohort:
                return cohort_resolver::class;
            case $objectvalue instanceof position:
                return position_resolver::class;
            default:
                throw new coding_exception('Unknown type provided for assigned_to union.');
        }
    }
}
