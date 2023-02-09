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
 * @package
 */
namespace container_workspace\webapi\resolver\type;

use container_workspace\formatter\file\file_formatter;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use container_workspace\file\file as model;
/**
 * Resolver for file type
 */
class file extends type_resolver {

    /**
     * @param string    $field
     * @param model     $file
     * @param array     $args
     * @param execution_context $ec
     *
     * @return mixed|null
     */
    public static function resolve(string $field, $file, array $args, execution_context $ec) {
        if (!($file instanceof model)) {
            throw new \coding_exception("The parameter source is invalid");
        }

        if ('author' === $field) {
            return $file->get_user();
        }

        if (!$ec->has_relevant_context()) {
            $workspace_id = $file->get_workspace_id();
            $context = \context_course::instance($workspace_id);

            $ec->set_relevant_context($context);
        }

        $context = $ec->get_relevant_context();
        $formatter = new file_formatter($file, $context);

        $format = null;
        if (isset($args['format'])) {
            $format = $args['format'];
        }

        return $formatter->format($field, $format);
    }
}