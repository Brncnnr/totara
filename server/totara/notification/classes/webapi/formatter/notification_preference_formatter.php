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
namespace totara_notification\webapi\formatter;

use context;
use core\json_editor\helper\document_helper;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;
use stdClass;
use totara_notification\local\schedule_helper;
use totara_notification\model\notification_preference as model;
use totara_notification\resolver\resolver_helper;

class notification_preference_formatter extends formatter {
    /**
     * notification_preference_formatter constructor.
     * @param model   $notification_preference
     * @param context $context
     */
    public function __construct(model $notification_preference, context $context) {
        $record = new stdClass();
        $record->id = $notification_preference->get_id();
        $record->title = $notification_preference->get_title();
        $record->overridden_additional_criteria = $notification_preference->is_overridden_additional_criteria();
        $record->additional_criteria = $notification_preference->get_additional_criteria();
        $record->body = $notification_preference->get_body();
        $record->subject = $notification_preference->get_subject();
        $record->body_format = $notification_preference->get_body_format();
        $record->resolver_class_name = $notification_preference->get_resolver_class_name();
        $record->overridden_body = $notification_preference->is_overridden_body();
        $record->overridden_subject = $notification_preference->is_overridden_subject();
        $record->overridden_recipient = $notification_preference->is_overridden_recipient();
        $record->ancestor_id = $notification_preference->get_ancestor_id();
        $record->is_custom = $notification_preference->is_custom_notification();
        $record->schedule_offset = $notification_preference->get_schedule_offset();
        $record->overridden_schedule = $notification_preference->is_overridden_schedule();
        $record->subject_format = $notification_preference->get_subject_format();
        $recipients = $notification_preference->get_recipients();
        $recipient = !empty($recipients) ? reset($recipients) : null;
        $record->recipient = $recipient;
        $record->enabled = $notification_preference->get_enabled();
        $record->overridden_enabled = $notification_preference->is_overridden_enabled();
        $record->forced_delivery_channels = $notification_preference->get_forced_delivery_channels();
        $record->overridden_forced_delivery_channels = $notification_preference->is_overridden_forced_delivery_channels();
        $record->recipients = $recipients;

        parent::__construct($record, $context);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ($field === 'resolver_name') {
            // Convert the event_name into an event_class_name so that
            // we can give back the value of event_class_name and it will
            // try to convert the event_class_name into a human readable event name.
            $field = 'resolver_class_name';
        }
        if ($field === 'schedule_label' || $field === 'schedule_type') {
            // Convert the schedule fields back into the schedule_offset, and calculate
            // the correct value based off of that.
            $field = 'schedule_offset';
        }

        return parent::get_field($field);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('resolver_name' === $field || 'schedule_label' === $field || 'schedule_type' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $that = $this;

        return [
            'id' => null,
            'body' => function (?string $value, text_field_formatter $formatter) use ($that): string {
                if (empty($value)) {
                    return '';
                }

                if (FORMAT_JSON_EDITOR == $that->object->body_format &&
                    !document_helper::looks_like_json($value, true)) {
                    // This is happening because the text that we are receiving at this point
                    // is properly from the language text and we want to convert it into a proper
                    // json document content from a normal text.
                    $value = document_helper::create_json_string_document_from_text($value);
                }

                // Displaying with under totara_tui formatter/renderer.
                $formatter->set_additional_options(['formatter' => 'totara_tui']);
                $formatter->disabled_pluginfile_url_rewrite();

                return $formatter->format($value);
            },
            'title' => null,
            'overridden_additional_criteria' => null,
            'additional_criteria' => null,
            'subject' => function (?string $value, text_field_formatter $formatter) use ($that): string {
                // Note: we are using text_field_formatter here because the subject is a content produced
                // from editor, not a normal input string field.
                if (empty($value)) {
                    return '';
                }

                if (FORMAT_JSON_EDITOR == $that->object->subject_format &&
                    !document_helper::looks_like_json($value, true)) {
                    // This is happening because the text that we are receiving at this point
                    // is properly from the language text and we want to convert it into a proper
                    // json document content from a normal text.
                    $value = document_helper::create_json_string_document_from_text($value);
                }

                // Displaying with under totara_tui formatter/renderer.
                $formatter->set_additional_options(['formatter' => 'totara_tui']);
                $formatter->disabled_pluginfile_url_rewrite();

                return $formatter->format($value);
            },
            'subject_format' => null,
            'body_format' => null,
            'resolver_name' => function (string $resolver_class_name): string {
                return resolver_helper::get_human_readable_resolver_name($resolver_class_name);
            },
            'schedule_offset' => function (int $offset, $unit): int {
                // Convert the offset from negative number.
                $offset = abs($offset);

                if (schedule_helper::DAY === $unit) {
                    $offset = $offset / DAYSECS;
                }

                return $offset;
            },
            'schedule_type' => function (int $offset): string {
                return schedule_helper::get_schedule_identifier($offset);
            },
            'schedule_label' => function (int $offset): string {
                return schedule_helper::get_human_readable_schedule_label($offset);
            },
            'overridden_body' => null,
            'overridden_subject' => null,
            'overridden_schedule' => null,
            'overridden_recipient' => null,
            'overridden_forced_delivery_channels' => null,
            'forced_delivery_channels' => null,
            'ancestor_id' => null,
            'resolver_class_name' => null,
            'is_custom' => null,
            'recipient' => null,
            'recipients' => null,
            'enabled' => null,
            'overridden_enabled' => null,
        ];
    }
}