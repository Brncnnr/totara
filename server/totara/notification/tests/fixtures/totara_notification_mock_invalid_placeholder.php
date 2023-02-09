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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_notification\placeholder\abstraction\placeholder;

/**
 * A simple placeholder that either implement {@see single_getter} nor {@see collection_getter}
 * because we want the process that expect either single_getter/collection_getter to fail.
 */
class totara_notification_mock_invalid_placeholder implements placeholder {
    /**
     * @return array
     */
    public static function get_options(): array {
        return [];
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        return false;
    }
}
