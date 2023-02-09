<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\webapi\formatter;

use core\webapi\formatter\formatter;
use totara_useraction\filter\applies_to;
use totara_useraction\filter\filter_contract;
use totara_useraction\filter\groups;
use totara_useraction\filter\duration;
use totara_useraction\filter\status;

/**
 * Formatter for the filter subtype of scheduled rules.
 */
class filters_formatter extends formatter {
    /**
     * The available fields. Because this isn't a regular model, we define them here instead.
     * @var string[]
     */
    private $fields = [
        'user_status',
        'duration',
        'applies_to',
    ];

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        return $this->object->{'filter_' . $field};
    }

    /**
     * Mapping of model fields to their relevant formatters.
     *
     * @return array
     */
    protected function get_map(): array {
        return [
            'user_status' => 'to_graphql',
            'duration' => 'to_graphql',
            'applies_to' => null,
        ];
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        return in_array($field, $this->fields);
    }

    public function to_graphql(filter_contract $filter) {
        return $filter->to_graphql();
    }
}
