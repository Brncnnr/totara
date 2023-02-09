<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_userstatus
 */

namespace totara_competency\formatter\profile;

use totara_competency\models\profile\proficiency_value;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;

/**
 * @property proficiency_value $object
 */
class scale_value_progress extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'scale_id' => null,
            'name' => string_field_formatter::class,
            'percentage' => null,
            'proficient' => null,
        ];
    }

    /**
     * Does the object has the given field,
     * can be overridden if formatter does not use stdClasses or entities
     *
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        return isset($this->object->$field);
    }

}