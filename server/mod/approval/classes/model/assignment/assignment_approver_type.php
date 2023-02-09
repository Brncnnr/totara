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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment;

use core_component;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\approver_type\approver_type;

/**
 * Class assignment_approver_type supports enum for the assignment_approver type field.
 *
 * @package mod_approval\model\assignment
 */
class assignment_approver_type {

    /**
     * Defined approver types.
     *
     * @var string[]|approver_type[]
     */
    private static $approver_types;

    /**
     * Get the defined approver types.
     *
     * @return array|approver_type[]|string[]
     */
    private static function get_approver_types(): array {
        if (is_null(self::$approver_types)) {
            $approver_types = core_component::get_namespace_classes(
                'model\assignment\approver_type',
                approver_type::class,
                'mod_approval'
            );
            $types = [];

            foreach ($approver_types as $approver_type) {
                $types[$approver_type::get_code()] = $approver_type;
            }
            self::$approver_types = $types;
        }

        return self::$approver_types;
    }

    /**
     * Is assignment_approver type code valid?
     *
     * @param int $type One of approver_type::TYPE_IDENTIFIER
     * @return bool
     */
    public static function is_valid(int $type): bool {
        return array_key_exists($type, self::get_approver_types());
    }

    /**
     * Translate a GraphQL enum value into the assignment_approver type code.
     *
     * @param string $enum One of mod_approval_approver_type enum
     * @return integer
     */
    public static function enum_to_code(string $enum): int {
        foreach (self::get_approver_types() as $code => $class) {
            if ($class::get_enum() === $enum) {
                return $code;
            }
        }
        throw new model_exception('Unknown approver type provided');
    }

    /**
     * Gets the instance of approver type.
     *
     * @param int $type
     *
     * @return approver_type
     * @throws model_exception
     */
    public static function get_instance(int $type): approver_type {
        $approver_types = self::get_approver_types();
        if (!isset($approver_types[$type])) {
            throw new model_exception("Unknown assignment_approver type code");
        }
        /** @var approver_type $approver_type*/
        $approver_type = $approver_types[$type];

        return new $approver_type();
    }

    /**
     * Get the list of assignment approver types
     *
     * @return array
     */
    public static function get_list(): array {
        $list = [];
        foreach (self::get_approver_types() as $class) {
            /** @var approver_type $instance */
            $instance = new $class();
            $list[] = [
                'label' => $instance->label(),
                'type' => $instance->get_enum(),
                'options' => $instance->options(),
            ];
        }
        return $list;
    }
}
