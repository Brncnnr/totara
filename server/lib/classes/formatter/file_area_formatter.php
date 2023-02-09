<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\formatter;

use core\files\file_area;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;
use context;

class file_area_formatter extends formatter {

    /**
     * @param file_area $object
     * @param context $context
     */
    public function __construct(file_area $object, context $context) {
        return parent::__construct($object, $context);
    }

    /**
     * @inheritDoc
     */
    protected function get_map(): array {
        return [
            'repository_id' => null,
            'draft_id' => null,
            'url' => string_field_formatter::class,
        ];
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function has_field(string $field): bool {
        $get_function = 'get_' . $field;
        if (!method_exists($this->object, $get_function)) {
            throw new \coding_exception('Tried to access a method which should exist but does not: ' . $get_function);
        }
        return true;
    }

    /**
     * @param string $field
     *
     * @return mixed
     */
    protected function get_field(string $field) {
        $get_function = 'get_' . $field;
        return $this->object->{$get_function}();
    }
}
