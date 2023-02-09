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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use core\collection;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_provider;

class relationship_helper {

    /**
     * Get the relationships that can be used for the performance activities feature.
     *
     * @return collection|relationship[]
     */
    public static function get_supported_perform_relationships(): collection {
        return (new relationship_provider())
            ->filter_by_component('mod_perform', true)
            ->get_compatible_relationships(['user_id']);
    }

}
