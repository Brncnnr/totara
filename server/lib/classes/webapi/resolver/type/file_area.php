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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\type;

use core\format;
use core\formatter\file_area_formatter;
use core\formatter\file_type_formatter;
use core\webapi\execution_context;
use core\webapi\type_resolver;

/**
 * Class file_area
 *
 * Resolves file area information for uploading files.
 *
 * @package core\webapi\resolver\type
 */
class file_area extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $file_area, array $args, execution_context $ec) {
        if (!$file_area instanceof \core\files\file_area) {
            throw new \coding_exception('Expected file_area');
        }

        $formatter = new file_area_formatter($file_area, \context_system::instance());
        $format = $args['format'] ?? format::FORMAT_PLAIN;

        return $formatter->format($field, $format);
    }

}