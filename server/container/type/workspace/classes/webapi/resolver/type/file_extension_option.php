<?php
/**
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\type;

use container_workspace\formatter\file\extension_option_formatter;
use core\webapi\execution_context;
use core\webapi\type_resolver;

/**
 * File extension type resolver
 */
class file_extension_option extends type_resolver {
    /**
     * General type resolver.
     * @param string $field the field name from type
     * @param mixed $source the date returned from type
     * @param array $args field arguments
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        $format = null;
        $context = null;

        if (isset($args['format'])) {
            $format = $args['format'];
        }

        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
        }

        $formatter = new extension_option_formatter($source, $context);
        return $formatter->format($field, $format);
    }
}