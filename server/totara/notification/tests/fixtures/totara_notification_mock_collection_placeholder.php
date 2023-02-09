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

use totara_notification\placeholder\abstraction\collection_placeholder;

class totara_notification_mock_collection_placeholder implements collection_placeholder {
    /**
     * @var array
     */
    private $map_data;

    /**
     * totara_notification_mock_collection_placeholder constructor.
     * @param array $map_data
     */
    public function __construct(array $map_data) {
        $this->map_data = $map_data;
    }

    /**
     * @param array $load_only_keys
     * @return array
     */
    public function get_collection_map(array $load_only_keys = []): array {
        if (empty($load_only_keys)) {
            return $this->map_data;
        }

        $map_result = [];
        foreach ($load_only_keys as $key) {
            $map_result[$key] = $this->map_data[$key] ?? '';
        }

        return $map_result;
    }

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