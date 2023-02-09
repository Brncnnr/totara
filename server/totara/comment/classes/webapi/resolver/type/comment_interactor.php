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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\webapi\resolver\type;

use totara_comment\interactor\comment_interactor as interactor;
use core\webapi\execution_context;
use core\webapi\type_resolver;

/**
 * Type resolver for comment_interactor
 */
class comment_interactor extends type_resolver {
    /**
     * @param string            $field
     * @param interactor        $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|null
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof interactor)) {
            throw new \coding_exception("Invalid parameter of source");
        }

        switch ($field) {
            case 'user_id':
                return $source->get_user_id();

            case 'comment_id':
                return $source->get_comment_id();

            case 'can_update':
                return $source->can_update();

            case 'can_delete':
                return $source->can_delete();

            case 'can_report':
                return $source->can_report();

            case 'can_reply':
                return $source->can_reply();

            case 'can_react':
                return $source->can_react();

            case 'reacted':
                return $source->reacted();

            case 'can_view_author':
                return $source->can_view_author();

            default:
                debugging("The field '{$field}' is not supported yet", DEBUG_DEVELOPER);
                return null;
        }
    }
}