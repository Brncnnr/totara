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

class sort implements option {

    /**
     * @var int
     */
    public const CREATED = 1;

    /**
     * @var int
     */
    public const POPULAR = 2;

    /**
     * @var int
     */
    public const ALPHABET = 3;

    /**
     * @var int
     */
    public const DATESHARED = 5;

    /**
     * @inheritDoc
     * @param int $value
     * @return string
     */
    public static function get_string(int $value): string {
        switch ($value) {
            case static::CREATED:
                return get_string('sortcreated', 'totara_engage');

            case static::POPULAR:
                return get_string('sortpopular', 'totara_engage');

            case static::ALPHABET:
                return get_string('sortalphabet', 'totara_engage');

            case static::DATESHARED:
                return get_string('dateshared', 'totara_engage');

            default:
                debugging("Invalid value '{$value}' for sort", DEBUG_DEVELOPER);
                return '';
        }
    }

    /**
     * @inheritDoc
     * @param string $constantname
     * @return int
     */
    public static function get_value(string $constantname): int {
        $constantname = strtoupper($constantname);
        $constant = "static::{$constantname}";

        if (!defined($constant)) {
            $cls = static::class;
            throw new \coding_exception(
                "No constant found for name '{$constantname}' within the class {$cls}"
            );
        }

        return constant($constant);
    }

    /**
     * @inheritDoc
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::CREATED:
                return 'CREATED';

            case static::POPULAR:
                return 'POPULAR';

            case static::ALPHABET:
                return 'ALPHABET';

            case static::DATESHARED:
                return 'DATESHARED';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_sort_column(int $constant): string {
        switch ($constant) {
            case static::CREATED:
                return 'timecreated desc';

            case static::POPULAR:
                return 'popularity desc';

            case static::ALPHABET:
                return 'name asc';

            case static::DATESHARED:
                return 'dateshared desc';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }
}