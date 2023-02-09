<?php
/**
 * This file is part of Totara Core
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
 * @package core
 */
namespace core\orm\entity\filter;

use core\orm\query\field;

/**
 * A filter for searching the fields with given keywords.
 */
class keywords extends filter {
    /**
     * @param array|string $fields The field that we search query with.
     */
    public function __construct($fields) {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        parent::__construct($fields);
    }

    /**
     * @return void
     */
    public function apply() {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/core/searchlib.php");

        $fields = $this->get_fields(true);
        if (empty($fields)) {
            // Nothing to apply, as the list of fields are empty.
            return;
        }

        $keywords = totara_search_parse_keywords($this->value);
        if (empty($keywords)) {
            // No keywords to search for.
            return;
        }

        [$search_sql, $search_parameters] = totara_search_get_keyword_where_clause(
            $keywords,
            $fields,
            SQL_PARAMS_NAMED,
        );

        $this->builder->where_raw($search_sql, $search_parameters);
    }

    /**
     * Returns the list of fields.
     *
     * @param bool $with_table Whether we should return the lis of fields with prefixed database table or not.
     *
     * @return string[]
     */
    public function get_fields(bool $with_table = false): array {
        $fields = $this->params[0];

        if ($with_table) {
            $builder = $this->builder;
            $fields = array_map(
                function (string $field) use ($builder): string {
                    $field = new field($field, $builder);
                    return $field->sql();
                },
                $fields
            );
        }

        return $fields;
    }
}