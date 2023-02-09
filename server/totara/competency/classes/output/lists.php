<?php
/*
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_core
 */

namespace totara_competency\output;

use totara_core\output\select;

defined('MOODLE_INTERNAL') || die();

class lists extends select {

    /**
     * Gets the data for the given row
     *
     * @param array $option
     * @return array [string $data]
     */
    private static function get_row_data(array $option) : array {
        return [
            'extra_data' => !empty($option['extra_data']) ? json_encode($option['extra_data']) : null,
            'columns' => $option['columns'],
            'actions' => $option['actions'] ?? [],
            'expandable' => !empty($option['expandable']),
            'has_children' => !empty($option['has_children']),
            'header' => !empty($option['header']),
            'id' => !empty($option['id']) ? $option['id'] : null,
            'active' => $option['active'] ?? false,
            'disabled' => $option['disabled'] ?? false,
        ];
    }

    /**
     * Create a list template.
     *
     * @param string $key
     * @param string $title
     * @param array $row_headers
     * @param array $rows
     * @param bool $select_enabled
     * @param string $expand_template
     * @param string $expand_template_api
     * @param array $expand_template_args
     * @param bool $hierarchy_enabled true if the list supports a hierarchy structure
     * @param string $no_results
     * @param bool $has_actions
     * @return lists
     */
    public static function create(
        string $key,
        string $title,
        array $row_headers,
        array $rows,
        bool $select_enabled = false,
        string $expand_template = '',
        string $expand_template_api = '',
        array $expand_template_args = [],
        bool $hierarchy_enabled = false,
        string $no_results = '',
        bool $has_actions = false
    ) : lists {
        $data = [
            'expandTemplate' => $expand_template,
            'expandTemplateWebservice' => $expand_template_api,
            'expandTemplateWebserviceArgs' => json_encode($expand_template_args),
            'select_enabled' => $select_enabled,
            'hierarchyEnabled' => $hierarchy_enabled,
            'row_header' => [],
            'rows' => [],
            'no_results' => !empty($no_results) ? $no_results : null,
            'has_actions' => $has_actions,
        ];

        foreach ($row_headers as $option) {
            $data['row_header'][] = static::get_row_data((array) $option);
        }

        foreach ($rows as $option) {
            $data['rows'][] = static::get_row_data((array) $option);
        }

        $data = array_merge((array) parent::get_base_template_data($key, $title), $data);

        return new static($data);
    }

}