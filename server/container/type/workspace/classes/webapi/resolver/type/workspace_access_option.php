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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\type;

use container_workspace\formatter\workspace\access_option_formatter;
use container_workspace\query\workspace\access;
use core\webapi\execution_context;
use core\webapi\type_resolver;

/**
 * Workspace access option
 */
class workspace_access_option extends type_resolver {
    /**
     * @param string $field
     * @param int $source
     * @param array $args
     * @param execution_context $ec
     * @return string
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec): string {
        if (!access::is_valid($source)) {
            throw new \coding_exception("Invalid access value");
        }

        $context = null;
        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
        }

        $formatter = new access_option_formatter($source, $context);
        $format = $args['format'] ?? null;

        return $formatter->format($field, $format);
    }
}