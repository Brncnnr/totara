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

namespace mod_approval\model\workflow\stage_type;

use coding_exception;
use core_component;

/**
 * Provider class for stage types.
 * Used for getting a stage type by ENUM or CODE.
 */
class provider {

    /**
     * Defined workflow stage types.
     *
     * @var base[]
     */
    private static $stage_types;

    /**
     * Get the stage types.
     *
     * @return array|base[]
     */
    public static function get_types(): array {
        if (is_null(self::$stage_types)) {
            $stage_types = core_component::get_namespace_classes(
                'model\workflow\stage_type',
                base::class,
                'mod_approval'
            );
            $types = [];

            foreach ($stage_types as $stage_type_class) {
                /** @var base $stage_type_class*/
                $types[$stage_type_class::get_sort_order()] = $stage_type_class;
            }
            ksort($types);
            self::$stage_types = $types;
        }

        return self::$stage_types;
    }

    /**
     * Get stage type class by code.
     *
     * @param int $code
     *
     * @return base|string
     */
    public static function get_by_code(int $code): string {
        $types = self::get_types();

        foreach ($types as $type) {
            if ($type::get_code() === $code) {
                return $type;
            }
        }
        throw new coding_exception("Undefined code for stage type");
    }

    /**
     * Get stage type class by enum.
     *
     * @param string $enum
     *
     * @return base|string
     */
    public static function get_by_enum(string $enum): string {
        $types = self::get_types();

        foreach ($types as $type) {
            if ($type::get_enum() === $enum) {
                return $type;
            }
        }
        throw new coding_exception("Undefined enum for stage type");
    }
}