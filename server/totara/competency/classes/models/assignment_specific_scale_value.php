<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use coding_exception;
use totara_hierarchy\entity\scale_value;

/**
 * Class assignment_specific_scale_value
 * This is a virtual model that represent a scale value for a specific competency assignment.
 * The proficiency flag is should be adjusted based on any assignment specific min proficient value override.
 * @see assignment_specific_scale
 *
 * @package totara_competency\models
 */
class assignment_specific_scale_value extends scale_value {

    /**
     * assignment_specific_scale_value constructor.
     *
     * @param scale_value $origin_scale_value
     * @param bool $proficient
     */
    public function __construct(scale_value $origin_scale_value, bool $proficient) {
        parent::__construct($origin_scale_value->to_array());
        $this->proficient = $proficient;
    }

    public static function repository_class_name(): string {
        throw new coding_exception('Assignment specific scale value cannot be refreshed or saved');
    }

    public function set_attribute($name, $value): assignment_specific_scale_value {
        if ($name === 'proficient') {
            return parent::set_attribute('proficient', $value);
        }

        throw new coding_exception('Attributes can not be set on assignment specific scale values');
    }

}