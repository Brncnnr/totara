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
use core\formatter\theme_file_formatter;
use core\webapi\execution_context;
use core\webapi\type_resolver;

/**
 * Class theme_file
 *
 * Resolves theme file information.
 *
 * @package core\webapi\resolver\type
 */
class theme_file extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $theme_file, array $args, execution_context $ec) {
        if (!$theme_file instanceof \core\theme\file\theme_file) {
            throw new \coding_exception('Expected theme_file');
        }

        $formatter = new theme_file_formatter($theme_file, \context_system::instance());
        $format = $args['format'] ?? format::FORMAT_PLAIN;

        return $formatter->format($field, $format);
    }

}