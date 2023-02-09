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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\webapi\resolver\type;

use core\formatter\linkmetadata_formatter;
use core\link\metadata_info;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use core\format;

class link extends type_resolver {

    /**
     * @param string            $field
     * @param metadata_info     $metadata_info
     * @param array             $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $metadata_info, array $args, execution_context $ec) {
        if (!$metadata_info instanceof metadata_info) {
            throw new \coding_exception('Accepting only metadata_info.');
        }

        $formatter = new linkmetadata_formatter($metadata_info, \context_system::instance());
        $format = $args['format'] ?? null;

        if (null === $format && in_array($field, ['title', 'description'])) {
            // Default to format_plain for those string fields.
            $format = format::FORMAT_PLAIN;
        }

        return $formatter->format($field, $format);
    }
}