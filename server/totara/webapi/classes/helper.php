<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi;

use totara_webapi\local\util;

final class helper {

    /**
     * Validate raw request before loading Totara config.
     *
     * @return void
     */
    public static function validate_environment(): void {
        require_once(__DIR__ . '/local/util.php');
        if (!file_exists(__DIR__ . '/../../../../config.php')) {
            util::send_error('Webapi entrypoint cannot be used from Totara web installer', 500);
        }
    }

}