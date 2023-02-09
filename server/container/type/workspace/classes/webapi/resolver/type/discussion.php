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

use container_workspace\formatter\discussion\discussion_formatter;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use container_workspace\discussion\discussion as model;
use container_workspace\interactor\discussion\interactor;

/**
 * Resolver for type discussion
 */
class discussion extends type_resolver {
    /**
     * @param string $field
     * @param model $source
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed|null
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $USER;

        if (!$source instanceof model) {
            throw new \coding_exception("Expecting the parameter to be a model");
        }

        switch ($field) {
            case 'owner':
                return $source->get_user();

            case 'discussion_interactor':
                $user_id = $USER->id;

                if (isset($args['user_id'])) {
                    $user_id = $args['user_id'];
                }

                return new interactor($source, $user_id);

            case 'comment_cursor':
                $cursor = new \totara_comment\pagination\cursor();
                $cursor->set_limit(1);

                return $cursor->encode();

            case 'edited':
                $time_modified = $source->get_time_modified();
                return null !== $time_modified && 0 !== $time_modified;

            default:
                $formatter = new discussion_formatter($source);
                $format = null;

                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                return $formatter->format($field, $format);
        }
    }
}