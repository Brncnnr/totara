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
namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use coding_exception;
use format_base;

class course_format extends type_resolver {
    /**
     * @param string            $field
     * @param format_base       $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $CFG;
        require_once("{$CFG->dirroot}/course/format/lib.php");

        if (!($source instanceof format_base)) {
            throw new coding_exception("Only course format object is accepted");
        }

        switch ($field) {
            case "name":
                return $source->get_format_name();

            case "format":
                return $source->get_format();

            case "has_course_view_page":
                return $source->has_view_page();

            default:
                throw new coding_exception("The field '{$field}' is not yet supported");
        }
    }
}