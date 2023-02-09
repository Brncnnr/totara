<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\query\option;


final class source implements option {
    /**
     * Created by the owner.
     * @var int
     */
    public const SELF = 1;

    /**
     * Created by other user.
     * @var int
     */
    public const OTHER = 2;

    /**
     * @inheritDoc
     * @param string $name
     * @return int
     */
    public static function get_value(string $name): int {
        $name = strtoupper($name);
        $constant = "static::{$name}";

        if (!defined($constant)) {
            throw new \coding_exception("No constant '{$constant}' defined");
        }

        return (int) constant($constant);
    }

    /**
     * @inheritDoc
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::SELF:
                return 'SELF';

            case static::OTHER:
                return 'OTHER';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case static::SELF:
                return get_string('self', 'totara_engage');

            case static::OTHER:
                return get_string('other', 'totara_engage');

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_self(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("The source's value is invalid '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::SELF == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_other(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("The source's value is invalid '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::OTHER == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        return in_array($value, [static::OTHER, static::SELF]);
    }
}