<?php
/**
 * This file is part of Totara Learn
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
 * @author  Qingyang Liu<qingyang.liu@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_core\extended_context as model;

/**
 * Type resolver for totara_notification_extended_context.
 */
class extended_context extends type_resolver {
    /**
     * @param string $field
     * @param model $extended_context
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $extended_context, array $args, execution_context $ec) {
        if (!($extended_context instanceof model)) {
            throw new coding_exception(
                "Invalid extended context passed to the resolver"
            );
        }

        switch ($field) {
            case 'context_id':
                return $extended_context->get_context_id();

            case 'component':
                return $extended_context->get_component();

            case 'item_id':
                return $extended_context->get_item_id();

            case 'area':
                return $extended_context->get_area();

            default:
                throw new coding_exception("The field '{$field}' had not yet supported");
        }
    }
}