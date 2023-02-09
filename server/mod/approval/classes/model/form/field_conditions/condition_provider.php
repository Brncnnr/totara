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
 */

namespace mod_approval\model\form\field_conditions;

use coding_exception;
use core_component;

/**
 * This class provides an interface for accessing defined conditions.
 */
class condition_provider {

    /**
     * List of defined conditions.
     *
     * @var condition[]
     */
    private static $defined_conditions;

    /**
     * Get the defined conditions.
     *
     * @return array
     */
    private static function get_defined_conditions() {
        if (is_null(self::$defined_conditions)) {
            $defined_conditions = core_component::get_namespace_classes(
                'model\form\field_conditions',
                condition::class,
                'mod_approval'
            );
            $conditions = [];

            foreach ($defined_conditions as $condition_class) {
                if (isset($conditions[$condition_class::IDENTIFIER])) {
                    $error_message = sprintf(
                        'A condition with the identifier "%s" has already been registered',
                        $condition_class::IDENTIFIER
                    );
                    throw new coding_exception($error_message);
                }
                $conditions[$condition_class::IDENTIFIER] = $condition_class;
            }
            self::$defined_conditions = $conditions;
        }

        return self::$defined_conditions;
    }

    /**
     * Get instance of condition with the specified identifier.
     *
     * @param string $identifier
     * @return condition
     */
    public static function get_instance(string $identifier): condition {
        /** @var condition[] $defined_conditions */
        $defined_conditions = self::get_defined_conditions();

        if (!isset($defined_conditions[$identifier])) {
            throw new coding_exception("unknown condition identifier '$identifier'");
        }
        $condition = $defined_conditions[$identifier];

        return new $condition();
    }
}
