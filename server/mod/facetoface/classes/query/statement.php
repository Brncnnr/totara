<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query;

defined('MOODLE_INTERNAL') || die();

/**
 * Just a class that only holds SQL and its parameters. It should only be constructed at query_interface's
 * child implementation.
 */
final class statement {
    /**
     * @var string
     */
    private $sql;

    /**
     * @var array
     */
    private $parameters;

    /**
     * statement constructor.
     *
     * @param string $sql
     * @param array $parameters
     */
    public function __construct(string $sql, array $parameters) {
        $this->sql = $sql;
        $this->parameters = $parameters;
    }

    /**
     * Returning SQL.
     *
     * @return string
     */
    public function get_sql(): string {
        return $this->sql;
    }

    /**
     * Returning SQL's parameters to perform the fetch.
     *
     * @return array
     */
    public function get_parameters(): array {
        return $this->parameters;
    }
}