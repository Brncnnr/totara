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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\resolver\type;

use coding_exception;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_notification\webapi\formatter\notifiable_event_user_preference as notifiable_event_user_preference_formatter;
use totara_notification\resolver\resolver_helper;

/**
 * Type resolver for totara_notification_notifiable_event_user_preference.
 */
class notifiable_event_user_preference extends type_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!is_array($source) ||
            !isset($source['resolver_class_name']) || !resolver_helper::is_valid_event_resolver($source['resolver_class_name']) ||
            !isset($source['user_id'])) {
            throw new coding_exception("Invalid source passed to the resolver");
        }

        $format = $args['format'] ?? format::FORMAT_HTML;
        $formatter = new notifiable_event_user_preference_formatter($source, context_system::instance());

        return $formatter->format($field, $format);
    }
}