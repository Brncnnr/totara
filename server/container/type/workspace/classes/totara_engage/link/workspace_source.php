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
 * @package container_workspace
 */

namespace container_workspace\totara_engage\link;

use totara_engage\link\source_generator;

/**
 * Generate the source link for workspaces
 *
 * @package container_workspace\totara_engage\link
 */
final class workspace_source extends source_generator {
    /**
     * @var array
     */
    protected static $required_attributes = ['id'];

    /**
     * @return string
     */
    public static function get_source_key(): string {
        return 'ws';
    }

    /**
     * @param array $source_params
     * @return array
     */
    public static function convert_source_to_attributes(array $source_params): array {
        // ID is required
        $params = [
            'id' => $source_params[0],
        ];
        if (isset($source_params[1]) && is_numeric($source_params[1])) {
            $params['tab'] = $source_params[1];
        }
        return $params;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function convert_attributes_to_source(array $attributes): array {
        $source = [
            $attributes['id'],
        ];

        if (!empty($attributes['tab'])) {
            $source[] = $attributes['tab'];
        }

        return $source;
    }
}