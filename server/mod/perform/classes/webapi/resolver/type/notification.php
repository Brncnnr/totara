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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\notification as notification_formatter;
use mod_perform\models\activity\notification as notification_model;

/**
 * Note: It is the responsibility of the query to ensure the user is permitted to see an activity.
 */
class notification extends type_resolver {

    /**
     * @param string $field
     * @param notification_model $notification
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $notification, array $args, execution_context $ec) {
        if (!$notification instanceof notification_model) {
            throw new \coding_exception('Expected notification model');
        }

        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $formatter = new notification_formatter($notification, $ec->get_relevant_context());
        return $formatter->format($field, $format);
    }
}