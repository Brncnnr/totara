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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recently_viewed
 */

namespace block_totara_recently_viewed\totara_engage\link;

use totara_engage\link\source_generator;

/**
 * Track links on the dashboard block recently viewed
 *
 * @package block_totara_recently_viewed\totara_engage\link
 */
final class block_source extends source_generator {
    /**
     * @return string
     */
    public static function get_source_key(): string {
        return 'bv';
    }

    /**
     * @param array $source_params
     * @return array
     */
    public static function convert_source_to_attributes(array $source_params): array {
        $is_dashboard = (int) current($source_params);
        return [
            'dashboard' => $is_dashboard !== 1,
        ];
    }

    /**
     * @param array $attributes
     * @return array|int[]
     */
    protected function convert_attributes_to_source(array $attributes): array {
        $is_dashboard = $attributes['dashboard'] ?? false;
        return $is_dashboard ? [] : [1];
    }
}