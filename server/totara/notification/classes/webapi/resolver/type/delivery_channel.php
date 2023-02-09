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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_notification\delivery\channel\delivery_channel as delivery_channel_source;

/**
 * Type resolver for totara_notification_notifiable_event.
 */
class delivery_channel extends type_resolver {
    /**
     * @param string $field
     * @param delivery_channel_source $source
     * @param array $args
     * @param execution_context $ec
     * @return mixed|null
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!is_a($source, delivery_channel_source::class, true)) {
            throw new coding_exception("Invalid delivery_channel source passed to the resolver");
        }

        if (is_string($source)) {
            /** @var delivery_channel_source $source */
            $source = call_user_func([$source, 'make']);
        }

        switch ($field) {
            case 'component':
                return $source->component;

            case 'label':
                return $source->label;

            case 'is_enabled':
                return $source->is_enabled;

            case 'is_sub_delivery_channel':
                return $source->is_sub_delivery_channel;

            case 'parent_component':
                return $source->parent;

            case 'display_order':
                return $source->display_order;

            default:
                throw new coding_exception("The field '{$field}' is not yet supported");
        }
    }
}