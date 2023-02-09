<?php
/*
 * This file is part of Totara Learn
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\validators;

global $CFG;
require_once($CFG->dirroot . '/lib/completionlib.php');

use Dompdf\Exception;
use totara_competency\achievement_configuration;
use totara_hierarchy\entity\competency;


/**
 * Validator for competency items
 */
class competency_item_validator implements criteria_item_validator_interface {

    /**
     * Validate a single competency item's validity
     * @param int $item_id
     * @return bool
     */
    public static function validate_item(int $item_id): bool {
        try {
            $competency = new competency($item_id);
            $config = new achievement_configuration($competency);
            return $config->user_can_become_proficient();
        } catch (Exception $e) {
            return false;
        }
    }

}
