<?php
/*
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\type;

use core\webapi\execution_context;

/**
 * Language strings
 */
class language_strings extends \core\webapi\type_resolver {
    public static function resolve(string $field, $strings, array $args, execution_context $ec) {
        $strings = (array)$strings;

        if (empty($strings->json_string) || $field != 'json_string') {
            return null;
        }
        return $strings->json_string;
    }
}