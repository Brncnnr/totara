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
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

use core\json_editor\helper\document_helper;
use totara_notification\recipient\manager;
use totara_program\totara_notification\recipient\site_admin;
use totara_notification\recipient\subject;
use totara_program\utils;

/**
 * Shortcut to do all the program and certification migration steps.
 *
 * @param string $resolver_class_name
 * @param bool[] $program_message_types_and_schedules where key is int $program_message_type and value is bool $schedule_is_before
 * @param bool $is_for_program
 * @param string $provider_name
 * @param string $provider_component
 * @param array $notification_class_names
 */
function totara_program_upgrade_migrate_messages(
    string $resolver_class_name,
    array $program_message_types_and_schedules,
    bool $is_for_program,
    string $provider_name,
    string $provider_component,
    array $notification_class_names
): void {
    global $DB;

    // Register all program built-in notifications.
    if ($is_for_program) {
        totara_notification_sync_built_in_notification('totara_program');
    } else {
        totara_notification_sync_built_in_notification('totara_certification');
    }

    foreach ($program_message_types_and_schedules as $program_message_type => $schedule_is_before) {
        totara_program_upgrade_migrate_message_instances(
            $program_message_type,
            $is_for_program,
            $schedule_is_before,
            $resolver_class_name
        );
    }

    totara_notification_migrate_notifiable_event_prefs(
        $resolver_class_name,
        $provider_name,
        $provider_component
    );

    // Go through built-in notifications.
    foreach ($notification_class_names as $notification_class_name) {
        $notification_preference_id = $DB->get_field('notification_preference', 'id',
            [
                'notification_class_name' => $notification_class_name,
                'context_id' => context_system::instance()->id,
                'component' => '',
                'area' => '',
                'item_id' => 0,
            ]
        );
        if ($notification_preference_id) {
            // Disable for all programs.
            totara_program_upgrade_disable_notification_instances(
                $notification_preference_id,
                $is_for_program
            );
            // Migrate preferences.
            totara_notification_migrate_notification_prefs(
                $notification_preference_id,
                $provider_name,
                $provider_component
            );
        }
    }
}

/**
 * Reads old program or certification messages of the given type and creates new custom messages based on the given resolver.
 *
 * - Placeholders are converted from old to new
 * - The user and manager messages are separated
 * - Only program OR certification instances are processed - call once for each type, potentially with different resolvers
 *
 * @param int $program_message_type
 * @param bool $is_for_program true for program, false for certification
 * @param bool $schedule_is_before true if the schedule is "X days before event", false if it is "on event" or "X days after event"
 * @param string $resolver_class_name
 */
function totara_program_upgrade_migrate_message_instances(
    int $program_message_type,
    bool $is_for_program,
    bool $schedule_is_before,
    string $resolver_class_name
): void {
    global $DB,$CFG;
    require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

    // Is the weka editor enabled? If so we use FORMAT_JSON_EDITOR as the format.
    $weka_enabled = in_array('weka', editors_get_enabled_names());

    $sql = "SELECT pm.*
              FROM {prog_message} pm
              JOIN {prog} prog ON prog.id = pm.programid
             WHERE pm.messagetype = :message_type";
    $sql .= $is_for_program ? " AND certifid IS NULL" : " AND certifid > 0";
    $messages = $DB->get_records_sql($sql, ['message_type' => $program_message_type]);

    foreach ($messages as $message) {
        $offset = $schedule_is_before ? -$message->triggertime : $message->triggertime;

        $recipients = ($program_message_type == MESSAGETYPE_EXCEPTION_REPORT)
            ? [site_admin::class]
            : [subject::class];

        $recipient = $program_message_type == MESSAGETYPE_EXCEPTION_REPORT ? site_admin::class : subject::class;

        // Subject message.
        if ($weka_enabled) {
            $format = FORMAT_JSON_EDITOR;
            $subject = document_helper::json_encode_document(
                document_helper::create_document_from_content_nodes([
                    totara_notification_convert_new_line_to_json_node(
                        totara_program_upgrade_convert_placeholders($message->messagesubject, $is_for_program)
                    )
                ])
            );
            $body = document_helper::json_encode_document(
                document_helper::create_document_from_content_nodes([
                    totara_notification_convert_new_line_to_json_node(
                        totara_program_upgrade_convert_placeholders($message->mainmessage, $is_for_program)
                    )
                ])
            );
        } else {
            $format = FORMAT_PLAIN;
            $subject = totara_program_upgrade_convert_placeholders($message->messagesubject, $is_for_program);
            $body = totara_program_upgrade_convert_placeholders($message->mainmessage, $is_for_program);
        }

        $notif_pref_override = [
            'resolver_class_name' => $resolver_class_name,
            'context_id' => context_program::instance($message->programid)->id,
            'component' => $is_for_program ? 'totara_program' : 'totara_certification',
            'area' => 'program',
            'item_id' => $message->programid,
            'enabled' => 1,
            'recipients' => json_encode($recipients),
            'recipient' => $recipient,
            'title' => totara_program_upgrade_convert_placeholders($message->messagesubject, $is_for_program),
            'subject' => $subject,
            'subject_format' => $format,
            'body' => $body,
            'body_format' => $format,
            'schedule_offset' => $offset,
            'forced_delivery_channels' => json_encode([]),
            'time_created' => time(),
        ];

        $DB->insert_record('notification_preference', $notif_pref_override);

        // Manager message.
        if ($message->notifymanager) {
            $message_managersubject = empty($message->managersubject) ? '' : $message->managersubject;
            $message_managermessage = empty($message->managermessage) ? '' : $message->managermessage;

            if ($weka_enabled) {
                $format = FORMAT_JSON_EDITOR;
                $subject = document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        totara_notification_convert_new_line_to_json_node(
                            totara_program_upgrade_convert_placeholders($message_managersubject, $is_for_program)
                        )
                    ])
                );
                $body = document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        totara_notification_convert_new_line_to_json_node(
                            totara_program_upgrade_convert_placeholders($message_managermessage, $is_for_program)
                        )
                    ])
                );
            } else {
                $format = FORMAT_PLAIN;
                $subject = totara_program_upgrade_convert_placeholders($message_managersubject, $is_for_program);
                $body = totara_program_upgrade_convert_placeholders($message_managermessage, $is_for_program);
            }

            $notif_pref_override = [
                'resolver_class_name' => $resolver_class_name,
                'context_id' => context_program::instance($message->programid)->id,
                'component' => $is_for_program ? 'totara_program' : 'totara_certification',
                'area' => 'program',
                'item_id' => $message->programid,
                'enabled' => 1,
                'recipients' => json_encode([manager::class]),
                'recipient' => manager::class,
                'title' => totara_program_upgrade_convert_placeholders($message_managersubject, $is_for_program),
                'subject' => $subject,
                'subject_format' => $format,
                'body' => $body,
                'body_format' => $format,
                'schedule_offset' => $offset,
                'forced_delivery_channels' => json_encode([]),
                'time_created' => time(),
            ];

            $DB->insert_record('notification_preference', $notif_pref_override);
        }

        // Make a backup of the message record to be deleted.
        totara_program_upgrade_backup_message($message->id, $is_for_program);

        // Remove the old message.
        $DB->delete_records('prog_message', ['id' => $message->id]);
    }
}

/**
 * Save a backup of a legacy message record as a file, just in case.
 *
 * @param int $message_id
 * @param bool $is_for_program
 */
function totara_program_upgrade_backup_message(int $message_id, bool $is_for_program): void {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/lib/moodlelib.php');

    $message_record = $DB->get_record('prog_message', ['id' => $message_id]);
    if (!$message_record) {
        return;
    }
    $program_context = context_program::instance($message_record->programid, IGNORE_MISSING);
    if (!$program_context) {
        return;
    }
    try {
        $fs = get_file_storage();
        $file_info = [
            'contextid' => $program_context->id,
            'component' => $is_for_program ? 'totara_program' : 'totara_certification',
            'filearea' => 'program_legacy_message_backup',
            'itemid' => $message_id,
            'filepath' => '/',
            'filename' => 'program_legacy_message_backup_' . $message_id . '.json'
        ];
        $fs->create_file_from_string($file_info, json_encode($message_record));
    } catch (moodle_exception $e) {
        // Don't let upgrade fail if there are problems creating the file.
    }
}

/**
 * Disables the given notification in all existing programs or certifications
 *
 * Note that this doesn't disabled the notification in the system context. The idea is that new programs will inherit
 * the system-context notification while existing programs will not, in order that they continue to use pre-existing
 * messages that have been migrated to custom notifications using totara_program_upgrade_migrate_message.
 *
 * @param int $notification_preference_id
 * @param bool $is_for_program true for program, false for certification
 */
function totara_program_upgrade_disable_notification_instances(
    int $notification_preference_id,
    bool $is_for_program
): void {
    global $DB;

    $notification_preference = $DB->get_record('notification_preference', ['id' => $notification_preference_id]);

    if (!empty($notification_preference->ancestor_id)) {
        throw new coding_exception(
            'Tried to disabled instances of a program notification which is not an ancestor'
        );
    }

    if (empty($notification_preference->notification_class_name)) {
        throw new coding_exception(
            'Tried to disabled instances of a program notification which is not built in'
        );
    }

    if ($notification_preference->context_id != context_system::instance()->id) {
        throw new coding_exception(
            'Tried to disabled instances of a program notification which is not defined in the system context'
        );
    }

    if ($is_for_program) {
        $progs = $DB->get_recordset_select('prog', "certifid IS NULL");
    } else {
        $progs = $DB->get_recordset_select('prog', "certifid > 0");
    }

    foreach ($progs as $prog) {
        $notif_pref_override = [
            'ancestor_id' => $notification_preference_id,
            'resolver_class_name' => $notification_preference->resolver_class_name,
            'notification_class_name' => $notification_preference->notification_class_name,
            'context_id' => context_program::instance($prog->id)->id,
            'component' => $is_for_program ? 'totara_program' : 'totara_certification',
            'area' => 'program',
            'item_id' => $prog->id,
            'enabled' => 0,
            'time_created' => time(),
        ];

        $DB->insert_record('notification_preference', $notif_pref_override);
    }

    $progs->close();
}

/**
 * Convert old message placeholders to new notifiable event placeholders in programs
 *
 * @param string $text
 * @return string
 * @param bool $is_for_program true for program, false for certification
 */
function totara_program_upgrade_convert_placeholders(string $text, bool $is_for_program): string {
    $prog_or_cert = $is_for_program ? 'program' : 'certification';
    $map = [
        '%programfullname%' => '[' . $prog_or_cert . ':full_name]',
        '%userfullname%' => '[subject:full_name]',
        '%username%' => '[subject:username]',
        '%completioncriteria%' => '[assignment:due_date_criteria]',
        '%duedate%' => '[assignment:due_date]',
        '%managername%' => '[managers:full_name]',
        '%manageremail%' => '[managers:email]',
        '%setlabel%' => '[course_set:label]',
    ];

    foreach ($map as $old_key => $new_key) {
        $text = str_replace($old_key, $new_key, $text);
    }

    return $text;
}

/**
 * Reads data from 'prog_assignment' table and migrates 'completiontime' data based on events to use
 * the new completionoffsetamount and completionoffsetunit fields.
 */
function totara_program_upgrade_migrate_relative_dates_data(): void {
    global $DB;

    // Migrate the data in batch
    $DB->transaction(static function () use ($DB) {
        $offset = 0;
        $limit = 10000;
        $has_items = true;
        while ($has_items) {
            $assignments = $DB->get_recordset_select(
                'prog_assignment',
                'completionevent > 0',
                null,
                '',
                '*',
                $offset,
                $limit
            );
            $has_items = $assignments->valid();
            $offset = $offset + $limit;

            foreach ($assignments as $assignment) {
                // This is an additional check whether the completiontime is valid.
                // This also helps to repeat the function without any data loss.
                if (!is_null($assignment->completiontime) && $assignment->completiontime != -1) {
                    if ($assignment->completiontime % utils::DURATION_YEAR == 0) {
                        $assignment->completionoffsetamount = $assignment->completiontime / utils::DURATION_YEAR;
                        $assignment->completionoffsetunit = utils::TIME_SELECTOR_YEARS;
                    } else if ($assignment->completiontime % utils::DURATION_MONTH == 0) {
                        $assignment->completionoffsetamount = $assignment->completiontime / utils::DURATION_MONTH;
                        $assignment->completionoffsetunit = utils::TIME_SELECTOR_MONTHS;
                    } else if ($assignment->completiontime % utils::DURATION_WEEK == 0) {
                        $assignment->completionoffsetamount = $assignment->completiontime / utils::DURATION_WEEK;
                        $assignment->completionoffsetunit = utils::TIME_SELECTOR_WEEKS;
                    } else {
                        $assignment->completionoffsetamount = floor($assignment->completiontime / utils::DURATION_DAY);
                        $assignment->completionoffsetunit = utils::TIME_SELECTOR_DAYS;
                    }

                    // For 0 we do want days
                    if ($assignment->completionoffsetamount == 0) {
                        $assignment->completionoffsetunit = utils::TIME_SELECTOR_DAYS;
                    }

                    $assignment->completiontime = null;

                    $DB->update_record('prog_assignment', $assignment);
                }
            }
        }
    });
}

/**
 * Migrate plain text body and/or subject formats to use json format if weka editor in enabled
 *
 * @param array $resolvers The resolver class names to update
 * @return bool
 */
function totara_program_upgrade_migrate_format_json(array $resolvers): bool {
    global $DB, $CFG;
    require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

    // If weka editor is not enabled, don't do anything.
    if (!in_array('weka', editors_get_enabled_names())) {
        return false;
    }

    // Get notifications where either the body, the subject or both are set as plain text format.
    list($resolver_insql, $resolver_inparams) = $DB->get_in_or_equal($resolvers, SQL_PARAMS_NAMED);

    $params = array_merge($resolver_inparams, [
        'subject_format' => FORMAT_PLAIN,
        'body_format' => FORMAT_PLAIN,
    ]);

    $sql = "SELECT *
              FROM {notification_preference}
             WHERE resolver_class_name {$resolver_insql}
               AND (subject_format = :subject_format OR body_format = :body_format)
               AND notification_class_name IS NULL";

    $records = $DB->get_recordset_sql($sql, $params);

    foreach($records as $record) {
        // If the subject format is plain text, migrate it to json format.
        if ($record->subject_format === FORMAT_PLAIN) {
            $record->subject_format = FORMAT_JSON_EDITOR;
            $record->subject = document_helper::json_encode_document(
                document_helper::create_document_from_content_nodes([
                    totara_notification_convert_new_line_to_json_node($record->subject)
                ])
            );
        }

        // If the body format is plain text, migrate it to json format.
        if ($record->body_format === FORMAT_PLAIN) {
            $record->body_format = FORMAT_JSON_EDITOR;
            $record->body = document_helper::json_encode_document(
                document_helper::create_document_from_content_nodes([
                    totara_notification_convert_new_line_to_json_node($record->body)
                ])
            );
        }

        $DB->update_record('notification_preference', $record);
    }

    $records->close();

    return true;
}
