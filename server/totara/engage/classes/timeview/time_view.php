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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\timeview;

use totara_engage\query\option\option;

final class time_view implements option {
    /**
     * Less than five minutes
     * @var int
     */
    public const LESS_THAN_FIVE = 1;

    /**
     * Five to ten minutes
     * @var int
     */
    public const FIVE_TO_TEN = 2;

    /**
     * More than ten minutes
     * @var int
     */
    public const MORE_THAN_TEN = 3;

    /**
     * Given the integer constant value, this function should be able to return
     * the string value that machine can also understand.
     *
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::LESS_THAN_FIVE:
                return 'LESS_THAN_FIVE';

            case static::FIVE_TO_TEN:
                return 'FIVE_TO_TEN';

            case static::MORE_THAN_TEN:
                return 'MORE_THAN_TEN';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}");
        }
    }

    /**
     * Given the integer constant value, this function should be able to return
     * a label string value that human can undertand. Not used for machine to understand.
     *
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case static::LESS_THAN_FIVE:
                return get_string('timeviewlessthanfive', 'totara_engage');

            case static::FIVE_TO_TEN:
                return get_string('timeviewfivetoten', 'totara_engage');

            case static::MORE_THAN_TEN:
                return get_string('timeviewmorethanten', 'totara_engage');

            default:
                throw new \coding_exception("Unable to find the string of time view with value '{$constant}'");
        }
    }

    /**
     * Given the string constant name, this function should be able to return
     * an integer constant value that associates with that name. The string constant name
     * must be something that a machine can understand.
     *
     * @param string $constantname
     * @return int
     */
    public static function get_value(string $constantname): int {
        $constantname = strtoupper($constantname);

        switch ($constantname) {
            case 'LESS_THAN_FIVE':
                return static::LESS_THAN_FIVE;

            case 'FIVE_TO_TEN':
                return static::FIVE_TO_TEN;

            case 'MORE_THAN_TEN':
                return static::MORE_THAN_TEN;

            default:
                throw new \coding_exception("Unable to find the value of time view with name '{$constantname}'");
        }
    }
}