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
    'totara_criteria\entities\criteria_item' => 'totara_criteria\entity\criteria_item',
    'totara_criteria\entities\criteria_item_record' => 'totara_criteria\entity\criteria_item_record',
    'totara_criteria\entities\criteria_metadata' => 'totara_criteria\entity\criteria_metadata',
    'totara_criteria\entities\criterion' => 'totara_criteria\entity\criterion',
    'totara_criteria\entities\criterion_item' => 'totara_criteria\entity\criterion_item',
    'totara_criteria\entities\criterion_repository' => 'totara_criteria\entity\criterion_repository',
);