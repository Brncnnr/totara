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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use jsoneditor_simple_multi_lang\json_editor\node\lang_block;
use jsoneditor_simple_multi_lang\json_editor\node\lang_blocks;

/**
 * Create any notification & notification recipient records that do not already exist for existing activities.
 *
 * @param array $notifications An array of class_key => default_trigger, defines what notifications to create records for.
 */
function mod_perform_upgrade_create_missing_notification_records(array $notifications) {
    global $DB;

    $notification_class_keys = array_keys($notifications);
    $time = time();
    $transaction = $DB->start_delegated_transaction();

    $activity_ids = $DB->get_fieldset_select('perform', 'id', '1 = 1');

    $notifications_to_insert = [];

    foreach ($activity_ids as $activity_id) {
        $existing_notifications = $DB->get_records_sql("
            SELECT notification.id, notification.class_key
            FROM {perform_notification} notification
            INNER JOIN {perform} activity
            ON notification.activity_id = activity.id
            WHERE activity.id = :activity_id
        ", ['activity_id' => $activity_id]);
        $existing_notification_class_keys = array_column($existing_notifications, 'class_key');
        $missing_notifications_class_keys = array_diff($notification_class_keys, $existing_notification_class_keys);
        $existing_notification_ids = array_column($existing_notifications, 'id');

        // Create missing notifications
        foreach ($missing_notifications_class_keys as $class_key) {
            $notifications_to_insert[] = (object) [
                'activity_id' => $activity_id,
                'class_key' => $class_key,
                'active' => 0, // Notification should always be disabled
                'triggers' => json_encode($notifications[$class_key], JSON_UNESCAPED_SLASHES),
                'created_at' => $time,
            ];
        }
    }

    $DB->insert_records_via_batch('perform_notification', $notifications_to_insert);

    mod_perform_upgrade_create_missing_notification_recipient_records();

    $transaction->allow_commit();
}

/**
 * Create any notification recipient records that do not already exist for existing activities.
 *
 * This should be called whenever a new totara_core_relationship is added that is relevant for mod_perform.
 */
function mod_perform_upgrade_create_missing_notification_recipient_records() {
    global $DB;

    // This corresponds to the relationships that can be used for perform, the IDs fetched here should match what is fetched in
    // \mod_perform\models\activity\helpers\relationship_helper::get_supported_perform_relationships() (or equivalent)
    $relationship_ids = $DB->get_fieldset_sql("
        SELECT id
        FROM {totara_core_relationship}
        WHERE component IS NULL
        OR component = :component
    ", ['component' => 'mod_perform']);

    $recipients_to_insert = [];

    $activity_ids = $DB->get_fieldset_select('perform', 'id', '1 = 1');

    foreach ($activity_ids as $activity_id) {
        // Create missing recipient records
        $existing_notification_ids = $DB->get_fieldset_select(
            'perform_notification', 'id', 'activity_id = :activity_id', ['activity_id' => $activity_id]
        );
        $existing_recipient_relationships = $DB->get_records_sql("
            SELECT recipient.id, recipient.core_relationship_id, notification.id AS notification_id
            FROM {perform_notification_recipient} recipient
            INNER JOIN {perform_notification} notification
            ON recipient.notification_id = notification.id
            WHERE notification.activity_id = :activity_id
        ", ['activity_id' => $activity_id]);
        $existing_recipient_relationships_map = [];
        foreach ($existing_recipient_relationships as $record) {
            $existing_recipient_relationships_map[$record->notification_id][] = $record->core_relationship_id;
        }

        foreach ($existing_notification_ids as $notification_id) {
            foreach ($relationship_ids as $relationship_id) {
                if (isset($existing_recipient_relationships_map[$notification_id]) &&
                    in_array($relationship_id, $existing_recipient_relationships_map[$notification_id])) {
                    continue;
                }
                $recipients_to_insert[] = (object) [
                    'notification_id' => $notification_id,
                    'core_relationship_id' => $relationship_id,
                    'active' => 0, // Recipient should always be disabled
                ];
            }
        }
    }

    $DB->insert_records_via_batch('perform_notification_recipient', $recipients_to_insert);
}

/**
 * Unwraps element_response.response_data json, to simple json encoded strings.
 * This removed the need for unwrapping code in client side components and server side validation and formatting.
 *
 * answer_text: long_text, short_text
 * answer_value: numeric_rating_scale
 * answer_option: custom_rating_scale, multi_choice_single, multi_choice_multi
 * date: date_picker
 */
function mod_perform_upgrade_unwrap_response_data() {
    global $DB;

    $possible_wrapping_fields = ['answer_text', 'answer_option', 'date', 'answer_value'];

    $existing_responses = $DB->get_recordset_select('perform_element_response', "response_data <> 'null'");
    foreach ($existing_responses as $existing_response) {
        $decoded_response_data = json_decode($existing_response->response_data, true);

        if (!is_array($decoded_response_data)) {
            continue;
        }

        $unwrapped = null;

        foreach ($possible_wrapping_fields as $possible_wrapping_field) {
            if (array_key_exists($possible_wrapping_field, $decoded_response_data)) {
                $unwrapped = $decoded_response_data[$possible_wrapping_field];
                break;
            }
        }

        if ($unwrapped) {
            $unwrapped_encoded = json_encode($unwrapped);

            $DB->set_field(
                'perform_element_response',
                'response_data',
                $unwrapped_encoded,
                ['id' => $existing_response->id]
            );
        }
    }
}

/**
 * Converts any existing long text responses to the new Weka JSON format.
 */
function mod_perform_upgrade_long_text_responses_to_weka_format() {
    global $DB;
    $transaction = $DB->start_delegated_transaction();
    $responses = $DB->get_recordset_sql("
        SELECT response.id, response.response_data
        FROM {perform_element_response} response
        INNER JOIN {perform_section_element} section_element ON response.section_element_id = section_element.id
        INNER JOIN {perform_element} element ON section_element.element_id = element.id
        WHERE element.plugin_name = 'long_text'
    ");

    // Wrap the text from each response in the proper Weka JSON
    foreach ($responses as $response) {
        if (empty($response->response_data)) {
            // Response is completely empty, so don't need to do anything.
            continue;
        }

        if ($response->response_data === 'null') {
            // A string with the value 'null' that is not encoded with JSON is invalid, and will cause problems.
            // Because it isn't encoded with JSON, it wouldn't have been entered in by the user and is safe to delete.
            $DB->set_field('perform_element_response', 'response_data', null, ['id' => $response->id]);
            continue;
        }

        $response_text = json_decode($response->response_data);
        if (!is_string($response_text)) {
            // Response has already been converted into Weka JSON
            continue;
        }

        $text_elements = [];

        // Analyse the response string and insert breaks where there are newline characters
        $unbroken_string = '';
        $response_length = strlen($response_text);
        for ($i = 0; $i < $response_length; $i++) {
            $char = $response_text[$i];

            if ($char === "\n" || $char === "\r") {
                if (!empty($unbroken_string)) {
                    $text_elements[] = ['type' => 'text', 'text' => $unbroken_string];
                    $unbroken_string = '';
                }
                $text_elements[] = ['type' => 'hard_break'];
                continue;
            }

            $unbroken_string .= $char;
        }
        $text_elements[] = ['type' => 'text', 'text' => $unbroken_string];

        $weka_response = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => $text_elements,
                ],
            ],
        ];

        // Must encode it in the same way javascript does with JSON.stringify()
        // Must be equivalent to \core\json_editor\helper\document_helper::json_encode_document()
        $encoded_response = json_encode(
            $weka_response,
            JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $DB->set_field(
            'perform_element_response',
            'response_data',
            $encoded_response,
            ['id' => $response->id]
        );
    }

    $transaction->allow_commit();
}

function mod_perform_upgrade_element_responses_to_include_timestamps() {
    global $DB;

    if ($DB instanceof sqlsrv_native_moodle_database) {
        $DB->execute("
            UPDATE er
            SET er.updated_at = ps.updated_at
            FROM {perform_element_response} er
                JOIN {perform_participant_section} ps ON ps.participant_instance_id = er.participant_instance_id
                JOIN {perform_section_element} pse ON pse.section_id = ps.section_id
                WHERE pse.id = er.section_element_id AND ps.participant_instance_id = er.participant_instance_id
                AND er.updated_at IS NULL
        ");

        $DB->execute("
            UPDATE er
            SET er.created_at = ps.updated_at
            FROM {perform_element_response} er
                JOIN {perform_participant_section} ps ON ps.participant_instance_id = er.participant_instance_id
                JOIN {perform_section_element} pse ON pse.section_id = ps.section_id
                WHERE pse.id = er.section_element_id AND ps.participant_instance_id = er.participant_instance_id
                AND er.created_at IS NULL
        ");
    } else {
        $DB->execute("
            UPDATE {perform_element_response} er
            SET updated_at = (
                SELECT ps.updated_at
                FROM {perform_participant_section} ps
                JOIN {perform_section_element} pse ON ps.section_id = pse.section_id
                WHERE pse.id = er.section_element_id AND ps.participant_instance_id = er.participant_instance_id
            )
            WHERE updated_at IS NULL
    ");

        $DB->execute("
            UPDATE {perform_element_response} er
            SET created_at = (
                SELECT ps.updated_at
                FROM {perform_participant_section} ps
                JOIN {perform_section_element} pse ON ps.section_id = pse.section_id
                WHERE pse.id = er.section_element_id AND ps.participant_instance_id = er.participant_instance_id
            )
            WHERE created_at IS NULL
    ");
    }
}

function mod_perform_upgrade_subject_instances_closed_at_times(): void {
    global $DB;

    // Corresponds to value in server/mod/perform/classes/state/subject_instance/closed.php
    $params = ['closure_code' => 10];

    // This is a best guess effort; the record's last updated time may or may not
    // have changed since the instance was actually closed.
    $sql = "
        UPDATE {perform_subject_instance}
           SET closed_at = updated_at
         WHERE availability = :closure_code
    ";

    $DB->execute($sql, $params);
}

function mod_perform_upgrade_track_repeating_trigger(): void {
    global $DB;

    // See mod_perform\models\activity\trigger\repeating\factory::create_trigger_from_repeating_type().
    $mappings = [
        0 => 'mod_perform\models\activity\trigger\repeating\after_creation',
        1 => 'mod_perform\models\activity\trigger\repeating\after_creation_and_completion',
        2 => 'mod_perform\models\activity\trigger\repeating\after_completion'
    ];

    foreach ($mappings as $repeating_type => $repeating_trigger) {
        $DB->set_field(
            'perform_track',
            'repeating_trigger',
            $repeating_trigger,
            ['repeating_type' => $repeating_type]
        );
    }
}

function mod_perform_remove_legacy_message_providers(): void {
    message_provider_uninstall('mod_perform');
}

/**
 * Generate a mutli-lang json doc for the given string.
 *
 * @param string $str_key
 * @param string $str_manager - to cut down on the amount of times we fetch it
 * @return string - the json_encoded doc
 */
function mod_perform_generate_notification_string($str_key, $str_manager): string {
    global $DB;

    // Define replacements for replacement vars here.
    $rep_keys = [
        'recipient_fullname' => '[recipient:full_name]',
        'subject_fullname' => '[subject_user:full_name]',
        'activity_name' => '[perform_activity:name]',
        'activity_type' => '[perform_activity:type]',
        'activity_link' => '[subject_instance:activity_name_link]',
        'instance_days_active' => '[subject_instance:days_active]',
        'conditional_duedate' => '[subject_instance:conditional_duedate]',
        'instance_days_remaining' => '[subject_instance:days_remaining]',
        'instance_duedate' => '[subject_instance:duedate]',
        'participant_selection_link' => '[subject_instance:participant_selection_link]',
    ];

    // Participant instance selection messages should use the subject_instance (participants dont exist yet).
    if (strpos($str_key, 'participant_selection') !== false) {
        $rep_keys['participant_relationship'] = '[subject_instance:recipient_relationship]';
    } else {
        $rep_keys['participant_relationship'] = '[participant_instance:relationship]';
    }

    // This can be overridden at context levels, but should do for what we need.
    $multilang_enabled = $DB->record_exists(
        'filter_active',
        [
            'filter' => 'multilang',
            'active' => 1
        ]
    );

    $translations = $str_manager->get_list_of_translations();
    if (count($translations) > 1 && $multilang_enabled) {
        // Generate multi-lang content.

        $lang_nodes = [];
        foreach ($translations as $lang_key => $lang_name) {
            $content = $str_manager->get_string($str_key, 'mod_perform', $rep_keys, $lang_key);
            $raw_node = [
                'type' => lang_block::get_type(),
                'attrs' => [
                    'lang' => $lang_key,
                ],
                'content' => [paragraph::create_json_node_from_text($content)]
            ];

            if (lang_block::validate_schema($raw_node)) {
                $lang_nodes[] = $raw_node;
            }
        }

        $raw_node = [
            'type' => lang_blocks::get_type(),
            'content' => $lang_nodes
        ];
    } else {
        $content = $str_manager->get_string($str_key, 'mod_perform', $rep_keys);
        $raw_node = paragraph::create_json_node_from_text($content);
    }

    $document = document_helper::create_document_from_content_nodes([
        $raw_node
    ]);

    return document_helper::json_encode_document($document);
}

/**
 * Get all the translated strings for each recipient in this notification.
 *
 * @param stdClass $notif - The notification we're doing this for
 * @param array $relations - The sites core relation array
 * @param array recipients - A list of all available recipients for the notification
 * @return array
 */
function mod_perform_get_notif_content($notif, $relations, $recipients): array {
    $str_manager = get_string_manager();

    $content = [];
    $content['title'] = $str_manager->get_string("notification_broker_{$notif->class_key}", "mod_perform");
    switch ($notif->class_key) {
        case 'participant_selection':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_participant_selection_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_participant_selection_{$key}_body", $str_manager);
            }
            break;
        case 'instance_created':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_instance_created_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_instance_created_{$key}_body", $str_manager);
            }
            break;
        case 'instance_created_reminder':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_instance_created_reminder_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_instance_created_reminder_{$key}_body", $str_manager);
            }
            break;
        case 'due_date_reminder':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_due_date_reminder_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_due_date_reminder_{$key}_body", $str_manager);
            }
            break;
        case 'due_date':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_due_date_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_due_date_{$key}_body", $str_manager);
            }
            break;
        case 'overdue_reminder':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_overdue_reminder_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_overdue_reminder_{$key}_body", $str_manager);
            }
            break;
        case 'completion':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_completion_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_completion_{$key}_body", $str_manager);
            }
            break;
        case 'reopened':
            foreach ($recipients as $recipient) {
                $rid = $relations[$recipient->core_relationship_id]->idnumber;
                $key = $rid == 'direct_report' ? 'manager' : $rid; // Direct report strings do not exist!

                $content[$rid]['subject'] = mod_perform_generate_notification_string("template_reopened_{$key}_subject", $str_manager);
                $content[$rid]['body'] = mod_perform_generate_notification_string("template_reopened_{$key}_body", $str_manager);
            }
            break;
        default :
            debugging('Custom perform notification found, please add an upgrade for it here.');
    }

    // We'll merge identical content into multi-recipient preferences later.
    return $content;
}


/**
 * Create the additional criteria for roles, based on old recipients.
 *
 * @param array $recipients
 * @return string
 */
function mod_perform_generate_additional_criteria($recipients): string {
    global $DB;

    $criteria = new stdClass();
    $criteria->recipients = [];

    $rec_relations = $DB->get_records('totara_core_relationship');
    foreach ($recipients as $recipient) {
        if (!$recipient->active) {
            continue;
        }

        $criteria->recipients[] = $rec_relations[$recipient->core_relationship_id]->idnumber;
    }

    return json_encode($criteria);
}

/**
 * Get the old recipient data for a given perform notification.
 *
 * @param stdClass $notif - The notification we're getting it for
 * @param array $relations - The sites core relation array, so we don't fetch it all the time.
 * @return array
 */
function mod_perform_get_recipients($notif, $relations): array {
    global $DB;

    switch ($notif->class_key) {
        case 'participant_selection':
            $recipients = [];
            $records = $DB->get_records('perform_notification_recipient', ['notification_id' => $notif->id]);

            // For some reason there are records for unavailable recipients we need to weed out.
            $available = ['subject', 'manager', 'managers_manager', 'appraiser', 'direct_report'];
            foreach ($records as $record) {
                $rid = $relations[$record->core_relationship_id]->idnumber;
                if (in_array($rid, $available)) {
                    $recipients[] = $record;
                }
            }

            return $recipients;
        case 'completion':
            $recipients = [];
            $records = $DB->get_records('perform_notification_recipient', ['notification_id' => $notif->id]);

            // For some reason there are records for unavailable recipients we need to weed out.
            foreach ($records as $record) {
                $rid = $relations[$record->core_relationship_id]->idnumber;
                if ($rid != 'perform_external') {
                    $recipients[] = $record;
                }
            }

            return $recipients;
        case 'instance_created':
        case 'instance_created_reminder':
        case 'due_date_reminder':
        case 'due_date':
        case 'overdue_reminder':
        case 'reopened':
            // These ones don't have any extra checks.
            return $DB->get_records('perform_notification_recipient', ['notification_id' => $notif->id]);
        default :
            debugging('Custom perform notification found, please add an upgrade for it here.');
    }
}

/**
 * Get the new recipient data for an old perform notification.
 *
 * @param stdClass $notif - The notification we're getting it for
 * @param array $relations - The sites core relation array
 * @param array $recipients - An array of the old recipient records
 * @return array
 */
function mod_perform_get_recipient_data($notif, $relations, $recipients): array {
    $criteria = null;
    $recipient_classes = [];

    foreach ($recipients as $recipient) {
        $rid = $relations[$recipient->core_relationship_id]->idnumber;

        switch ($notif->class_key) {
            case 'participant_selection':
                $recipient_classes[] = "mod_perform\\totara_notification\\recipient\\participant_selector_{$rid}";
                break;
            case 'completion':
                $recipient_classes[] = "mod_perform\\totara_notification\\recipient\\{$rid}";
                break;
            case 'instance_created':
            case 'instance_created_reminder':
            case 'due_date_reminder':
            case 'due_date':
            case 'overdue_reminder':
            case 'reopened':
                if ($criteria === null) {
                    // Create a criteria object.
                    $criteria = new stdClass();
                    $criteria->recipients = [];
                }

                $criteria->recipients[] = $rid;
                $recipient_classes = ['mod_perform\totara_notification\recipient\participant'];
                break;
            default :
                debugging('Custom perform notification found, please add an upgrade for it here.');
        }
    }

    return [$recipient_classes, $criteria];
}

/**
 * Disable the new default notifications for the existing activity to avoid spam.
 *
 * @param $context - the context of the activity
 * @param array $defaults - A list of default notifications to disable.
 * @param int $now - The current timestamp (for consistency)
 * @return void
 */
function mod_perform_disable_default_notifications($context, $defaults, $now): void {
    global $DB;

    // Loop over defaults and create an activity level override to disable.
    foreach ($defaults as $default) {
        $record = new \stdClass();
        $record->ancestor_id = $default->id;
        $record->enabled = false;
        $record->context_id = $context->id;
        $record->time_created = $now;
        $record->resolver_class_name = $default->resolver_class_name;

        $DB->insert_record('notification_preference', $record);
    }
}

/**
 * Get the new resolver class for an old perform notification.
 *
 * @param stdClass $notif - The notification we're getting it for
 * @return string
 */
function mod_perform_get_notif_class($notif): string {
    // Now add some more specifics.
    switch ($notif->class_key) {
        case 'participant_selection':
            return 'mod_perform\totara_notification\resolver\participant_selection_resolver';
        case 'instance_created':
            return 'mod_perform\totara_notification\resolver\participant_instance_created_resolver';
        case 'instance_created_reminder':
            return 'mod_perform\totara_notification\resolver\participant_instance_created_resolver';
        case 'due_date_reminder':
            return 'mod_perform\totara_notification\resolver\participant_due_date_resolver';
        case 'due_date':
            return 'mod_perform\totara_notification\resolver\participant_due_date_resolver';
        case 'overdue_reminder':
            return 'mod_perform\totara_notification\resolver\participant_due_date_resolver';
        case 'completion':
            return 'mod_perform\totara_notification\resolver\participant_completion_resolver';
        case 'reopened':
            return 'mod_perform\totara_notification\resolver\participant_reopened_activity_resolver';
        default :
            debugging('Custom perform notification found, please add an upgrade for it here.');
    }
}

/**
 * Lets try and match up notifications with duplicate content into a single multi-recipient preference.
 *
 * @param array $notif - Tthe old notif record
 * @param array $contents - The content generated for the new notification
 * @param array $recipients - All recipients of the notification
 * @param array $relations - All relations for the activity
 * @return array - A multi-dimensional array of preferences to create.
 */
function mod_perform_merge_dulicate_preferences($notif, $contents, $recipients, $relations) {

    $preferences = [];
    foreach ($recipients as $recipient) {
        $relation = $relations[$recipient->core_relationship_id];
        $rid = $relation->idnumber;
        $body = $contents[$rid]['body'];
        $subject = $contents[$rid]['subject'];

        $matched = false;
        foreach ($preferences as $key => $preference) {
            if ($body == $preference['body'] && $subject == $preference['subject']) {
                // We have a duplicate.
                $matched = $key;
            }
        }

        // Disclaimer: This is a bit of a Bodge.
        $relation_active = $relation->active ?? false;
        if ($notif->class_key == 'participant_selection') {
            // Force this check as true for this 1 notif.
            $relation_active = true;
        }

        // If the contents match, add to the recipients list.
        if ($matched !== false) {
            if ($notif->active && $recipient->active && $relation_active) {
                // Gather all the enabled recipients.
                $preferences[$matched]['enabled_recipients'][$rid] = $recipient;
            } else {
                // We only really need to worry about these if there aren't any enabled recipients.
                $preferences[$matched]['disabled_recipients'][$rid] = $recipient;
            }
        } else {
            // It doesn't match anything, so add a new item.
            $preferences[$rid] = [];
            $preferences[$rid]['title'] = $contents['title'];
            $preferences[$rid]['subject'] = $subject;
            $preferences[$rid]['body'] = $body;
            $preferences[$rid]['enabled_recipients'] = [];
            $preferences[$rid]['disabled_recipients'] = [];

            if ($notif->active && $recipient->active && $relation_active) {
                // Gather all the enabled recipients.
                $preferences[$rid]['enabled_recipients'][$rid] = $recipient;
            } else {
                // We only really need to worry about these if there aren't any enabled recipients.
                $preferences[$rid]['disabled_recipients'][$rid] = $recipient;
            }
        }
    }

    return $preferences;
}

/**
 * Check whether relations are able to view or answer any of the sections in the activity.
 *
 * @param array $relations - the core_relations database objects
 * @param stdClass $activity - the database object for the current perform activity
 * @return array - the same array or relations, but with $active added to it
 */
function mod_perform_check_relations_participance($relations, $activity) {
    global $DB;

    $rel_info = $DB->get_records_sql(
        'SELECT rel.core_relationship_id, MAX(can_view) as can_view, MAX(can_answer) as can_answer
           FROM {perform_section_relationship} rel
           JOIN {perform_section} sec
             ON rel.section_id = sec.id
          WHERE sec.activity_id = :act_id
       GROUP BY rel.core_relationship_id
       ',
       [
           'act_id' => $activity->id,
       ]
    );

    // Nothing to see here, carry on with the default relations for the migration.
    if (empty($rel_info)) {
        return $relations;
    }

    // Lets duplicate the relations array and use that to avoid any cross contamination between activities.
    $act_relations = [];
    foreach ($relations as $key => $act_rel) {
        $act_relations[$key] = $act_rel;
        $act_relations[$key]->active = false;

        if (!empty($rel_info[$act_rel->id])) {
            $can_view = $rel_info[$act_rel->id]->can_view;
            $can_answer = $rel_info[$act_rel->id]->can_answer;
            $act_relations[$key]->active = $can_view || $can_answer;
        }
    }

    return $act_relations;
}

/**
 * @param stdClass $activity - the database object for the perform activity.
 * @param array $relations - The sites core relation array, so we don't fetch it all the time.
 * @param array $defaults - A list of default notifications to disable.
 * @param int $time - a timestamp to allow all time_created to be set uniformly.
 * @return bool
 */
function mod_perform_migrate_notifications($activity, $relations, $defaults, $time = null) {
    global $DB;

    // Make sure we have a time.
    $now = $time ?? time();

    // Fetch the context id for the activity record.
    $sql = "
        SELECT ctx.*
          FROM {context} ctx
          JOIN {course_modules} cm
            ON ctx.instanceid = cm.id
          JOIN {modules} md
            ON cm.module = md.id
         WHERE ctx.contextlevel = :ctx_level
           AND cm.course = :crs_id
           AND md.name = 'perform'
    ";
    $context = $DB->get_record_sql($sql, ['ctx_level' => 70, 'crs_id' => $activity->course]);

    // If there are existing CN overrides at the activities context level don't create more.
    if ($DB->record_exists('notification_preference', ['context_id' => $context->id])) {
        return false; // Mostly for tests.
    } else {
        mod_perform_disable_default_notifications($context, $defaults, $now);

        // Add the active field to the core relations for this activity.
        $act_relations = mod_perform_check_relations_participance($relations, $activity);

        // Loop over all active notifications for the activity.
        $notifs = $DB->get_records('perform_notification', ['activity_id' => $activity->id]);
        foreach ($notifs as $notif) {
            // Due date reminders are 'Before' all other schedules are 'After'
            $offset_modifier = $notif->class_key == 'due_date_reminder' ? -1 : 1;

            // Fetch all available recipients for the notification.
            $recipients = mod_perform_get_recipients($notif, $act_relations);

            // Prepare all the content strings for the notification.
            $content = mod_perform_get_notif_content($notif, $act_relations, $recipients);

            // Merge identical items into multi-receipient records.
            $preferences = mod_perform_merge_dulicate_preferences($notif, $content, $recipients, $act_relations);

            // Set up some default notification data.
            $record = new \stdClass();
            $record->subject_format = 5; // Hardcoded Json format.
            $record->body_format = 5; // Hardcoded Json format.
            $record->ancestor_id = null; // This should be custom to the perform activity.
            $record->forced_delivery_channels = json_encode([]); // Just use the default delivery channels.
            $record->resolver_class_name = mod_perform_get_notif_class($notif);
            $record->context_id = $context->id;
            $record->time_created = $now;

            // Loop over each schedule.
            $triggers = json_decode($notif->triggers);
            $offsets = !empty($triggers) ? $triggers : [0];
            foreach ($offsets as $offset) {

                 foreach ($preferences as $preference) {
                    $notification = clone($record);
                    $notification->schedule_offset = $offset * $offset_modifier;

                    // This is enabled if both the notification and recipient are active.
                    if (!empty($preference['enabled_recipients'])) {
                        $notification->enabled = true;
                        $recipients = $preference['enabled_recipients'];
                    } else {
                        // Make a disabled notif to maintain string customisations.
                        $notification->enabled = false;
                        $recipients = $preference['disabled_recipients'];
                    }

                    $title_extra = [];
                    foreach ($recipients as $recipient) {
                        $title_extra[] = $act_relations[$recipient->core_relationship_id]->idnumber;
                    }

                    $notification->title = $preference['title'] . ' (' . implode(', ', $title_extra) . ')';
                    $notification->subject = $preference['subject'];
                    $notification->body = $preference['body'];

                    // Transform recipient data to make it CN usables.
                    list($recipient_classes, $criteria) = mod_perform_get_recipient_data($notif, $act_relations, $recipients);

                    // Grab a recipient from the array, then encode the array for the DB.
                    $notification->recipient = current($recipient_classes); // Set any of the recipients.
                    $notification->recipients = json_encode($recipient_classes);
                    $notification->additional_criteria = empty($criteria) ? null : json_encode($criteria);

                    $DB->insert_record('notification_preference', $notification);
                }
            }

            // Now mark old notifs as disabled just in case.
            if ($notif->active == 1) {
                $notif->active = 0;
                $notif->updated_at = $now;
                $DB->update_record('perform_notification', $notif);
            }
        }
    }

    return true;
}
