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

namespace mod_approval\model\assignment\assignment_type;

use core_component;
use mod_approval\exception\model_exception;

/**
 * Provides the assignment types defined.
 *
 * @package mod_approval\model\assignment
 */
class provider {

    /**
     * @var base[]|array
     */
    private static $assignment_types;

    /**
     * Get all assignment types.
     *
     * @return array|base[]
     */
    public static function get_all() {
        if (is_null(self::$assignment_types)) {
            /** @var base[] $assignment_types*/
            $assignment_types = core_component::get_namespace_classes(
                'model\assignment\assignment_type',
                base::class,
                'mod_approval'
            );
            $types = [];

            foreach ($assignment_types as $assignment_type) {
                $types[$assignment_type::get_sort_order()] = $assignment_type;
            }
            ksort($types);
            self::$assignment_types = $types;
        }

        return self::$assignment_types;
    }

    /**
     * Get assignment type class identified by the code.
     *
     * @param int $code
     * @return base|string
     */
    public static function get_by_code(int $code): string {
        $assignment_type = null;

        /** @var base[] $types*/
        $types = self::get_all();

        foreach ($types as $type) {
            if ($type::get_code() === $code) {
                $assignment_type = $type;
                break;
            }
        }

        if (is_null($assignment_type)) {
            throw new model_exception("Unknown assignment type code: $code");
        }

        return $assignment_type;
    }

    /**
     * Get assignment type class identified by the enum.
     *
     * @param string $enum
     * @return base|string
     */
    public static function get_by_enum(string $enum): string {
        $assignment_type = null;

        /** @var base[] $types*/
        $types = self::get_all();

        foreach ($types as $type) {
            if ($type::get_enum() === $enum) {
                $assignment_type = $type;
                break;
            }
        }

        if (is_null($assignment_type)) {
            throw new model_exception("Unknown assignment type enum: $enum");
        }

        return $assignment_type;
    }
}