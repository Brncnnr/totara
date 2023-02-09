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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\resolver\type;

use coding_exception;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_notification\local\schedule_helper;
use totara_notification\model\notification_preference as model;
use totara_notification\model\notification_preference_value as model_value;
use totara_notification\resolver\resolver_helper;
use totara_notification\webapi\formatter\notification_preference_formatter;

class notification_preference_v2 extends type_resolver {
    /**
     * @param string            $field
     * @param model             $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof model)) {
            throw new coding_exception("Expected notification preference model");
        }

        if ('resolver' === $field) {
            return $source->get_resolver_class_name();
        } else if ('resolver_component' === $field) {
            $resolver_class_name = $source->get_resolver_class_name();
            return resolver_helper::get_component_of_resolver_class_name($resolver_class_name);
        } else if ('resolver_plugin_name' === $field) {
            $resolver_class_name = $source->get_resolver_class_name();
            return resolver_helper::get_human_readable_plugin_name($resolver_class_name);
        } else if ('parent_id' === $field) {
            $parent = $source->get_parent();
            return (null === $parent) ? null : $parent->get_id();
        } else if ('parent_value' === $field) {
            $parent = $source->get_parent();

            if (null === $parent) {
                if ($source->is_custom_notification()) {
                    return null;
                }

                // Righty, so the current notification preference is not a custom
                // notification, hence we are returning the built_in notification class
                // instead. Which will help us fallback to the system defined values.
                $built_in_class_name = $source->get_notification_class_name();
                return model_value::from_built_in_notification($built_in_class_name);
            }

            return model_value::from_parent_notification_preference($parent);
        } else if ('extended_context' === $field) {
            return $source->get_extended_context();
        }

        $context = context_system::instance();
        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
        }

        // Note, we will have to do sort of caching to help improve the speed performance.
        // Because for every fetching body/subject/title and all sort of other fields,
        // our model will try to look up DB for its parent, unless its parent is already
        // fetched in the model itself.
        $formatter = new notification_preference_formatter($source, $context);
        $format = null;

        if (in_array($field, ['body', 'subject'])) {
            // For these fields, we are defaulting the format to format raw.
            // Because we would want these fields to be formatted as raw content
            // when the format argument is not provided.
            $format = $args['format'] ?? format::FORMAT_RAW;
        } else if ('schedule_offset' === $field) {
            $format = $args['unit'] ?? schedule_helper::SECOND;
        }

        return $formatter->format($field, $format);
    }
}