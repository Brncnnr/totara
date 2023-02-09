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
use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\type_resolver;
use totara_notification\local\schedule_helper;
use totara_notification\model\notification_preference_value as model;

/**
 * Resolver for type 'totara_notification_notification_preference_value'.
 */
class notification_preference_value extends type_resolver {
    /**
     * @param string            $field
     * @param model             $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof model)) {
            throw new coding_exception(
                "Invalid source passed to the resolver"
            );
        }

        // Default to context system. However if the execution context has one then we will use that.
        $context = context_system::instance();
        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
        }

        switch ($field) {
            case 'body':
                $body_text = $source->get_body();
                $body_format = $source->get_body_format();

                if (FORMAT_JSON_EDITOR == $body_format &&
                    !document_helper::looks_like_json($body_format, true)) {
                    // This is happening because the text that we are receiving at this point
                    // is properly from the language text and we want to convert it into a proper
                    // json document content from a normal text.
                    $body_text = document_helper::create_json_string_document_from_text($body_text);
                }

                $formatter = new text_field_formatter(
                    $args['format'] ?? format::FORMAT_RAW,
                    $context
                );

                return $formatter->format($body_text);

            case 'body_format':
                return $source->get_body_format();

            case 'subject_format':
                return $source->get_subject_format();

            case 'subject':
                $subject_text = $source->get_subject();
                $subject_format = $source->get_subject_format();

                if (FORMAT_JSON_EDITOR == $subject_format &&
                    !document_helper::looks_like_json($subject_text, true)) {
                    // This is happening because the text that we are receiving at this point
                    // is properly from the language text and we want to convert it into a proper
                    // json document content from a normal text.
                    $subject_text = document_helper::create_json_string_document_from_text($subject_text);
                }

                $formatter = new string_field_formatter(
                    $args['format'] ?? format::FORMAT_RAW,
                    $context
                );

                return $formatter->format($subject_text);

            case 'title':
                return $source->get_title();

            case 'additional_criteria':
                return $source->get_additional_criteria();

            case 'schedule_offset':
                $format = $args['unit'] ?? schedule_helper::SECOND;
                $offset = $source->get_scheduled_offset();

                // Convert into positive number, as before event is resulting into a negative number.
                $offset = abs($offset);
                if (schedule_helper::DAY === $format) {
                    $offset = $offset / DAYSECS;
                }

                return $offset;

            case 'schedule_type':
                return schedule_helper::get_schedule_identifier($source->get_scheduled_offset());

            case 'recipient':
                return $source->get_recipient();

            case 'enabled':
                return $source->get_enabled();

            case 'forced_delivery_channels':
                return $source->get_forced_delivery_channels();

            case 'recipients':
                return $source->get_recipients();

            default:
                throw new coding_exception(
                    "Invalid field '{$field}' is not yet supported"
                );
        }
    }
}