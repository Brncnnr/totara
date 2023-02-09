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
 */

/**
 * This assists with autoloading when a class or its namespace has been renamed.
 * See lib/db/renamedclasses.php for further information on this type of file.
 */

defined('MOODLE_INTERNAL') || die();

// The old class name is the key, the new class name is the value.
// The array must be called $renamedclasses.

$renamedclasses = array(
    'totara_hierarchy\entities\hierarchy_framework' => 'totara_hierarchy\entity\hierarchy_framework',
    'totara_hierarchy\entities\hierarchy_framework_repository' => 'totara_hierarchy\entity\hierarchy_framework_repository',
    'totara_hierarchy\entities\hierarchy_item' => 'totara_hierarchy\entity\hierarchy_item',
    'totara_hierarchy\entities\hierarchy_item_repository' => 'totara_hierarchy\entity\hierarchy_item_repository',
    'totara_hierarchy\entities\hierarchy_type' => 'totara_hierarchy\entity\hierarchy_type',
    'hierarchy_organisation\entities\organisation' => 'hierarchy_organisation\entity\organisation',
    'hierarchy_organisation\entities\organisation_filters' => 'hierarchy_organisation\entity\organisation_filters',
    'hierarchy_organisation\entities\organisation_framework' => 'hierarchy_organisation\entity\organisation_framework',
    'hierarchy_organisation\entities\organisation_framework_repository' => 'hierarchy_organisation\entity\organisation_framework_repository',
    'hierarchy_organisation\entities\organisation_repository' => 'hierarchy_organisation\entity\organisation_repository',
    'hierarchy_position\entities\position' => 'hierarchy_position\entity\position',
    'hierarchy_position\entities\position_filters' => 'hierarchy_position\entity\position_filters',
    'hierarchy_position\entities\position_framework' => 'hierarchy_position\entity\position_framework',
    'hierarchy_position\entities\position_framework_repository' => 'hierarchy_position\entity\position_framework_repository',
    'hierarchy_position\entities\position_repository' => 'hierarchy_position\entity\position_repository',
);

