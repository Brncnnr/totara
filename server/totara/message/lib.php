<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @package totara
 * @subpackage message
 */

defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;
use totara_message\entity\message_metadata;

// block display limits
define('TOTARA_MSG_ALERT_LIMIT', 5);
define('TOTARA_MSG_TASK_LIMIT', 5);

require_once('messagelib.php');

/**
 * Get the language string  and icon for the message status
 *
 * @param $msgstatus int message status
 * @return array('text' => , 'icon' => '')
 */
function totara_message_msgstatus_text(int $msgstatus): array {
    if ($msgstatus == TOTARA_MSG_STATUS_OK) {
        $status = 'go';
        $text = get_string('statusok', 'block_totara_alerts');
    } else if ($msgstatus == TOTARA_MSG_STATUS_NOTOK) {
        $status = 'stop';
        $text = get_string('statusnotok', 'block_totara_alerts');
    } else {
        $status = 'grey_undecided';
        $text = get_string('statusundecided', 'block_totara_alerts');
    }
    return ['text' => $text, 'icon' => 't/' . $status];
}

/**
 * Get the language string  and icon for the message urgency
 *
 * @param $urgency int message urgency
 * @return array('text' => , 'icon' => '')
 */
function totara_message_urgency_text(int $urgency): array {
    if ($urgency == TOTARA_MSG_URGENCY_URGENT) {
        $level = 'stop';
        $text = get_string('urgent', 'block_totara_alerts');
    } else {
        $level = 'go';
        $text = get_string('normal', 'block_totara_alerts');
    }
    return ['text' => $text, 'icon' => 't/' . $level];
}

/**
 * Get the short name of the message type
 *
 * @param $msgtype int message urgency
 * @return string
 */
function totara_message_cssclass(int $msgtype): string {
    global $TOTARA_MESSAGE_TYPES;

    return $TOTARA_MESSAGE_TYPES[$msgtype];
}

/**
 * Get the language string  and icon for the message type
 *
 * @param $msgtype int message type
 * @return array('text' => '', 'icon' => '')
 */
function totara_message_msgtype_text(int $msgtype): array {
    global $CFG, $TOTARA_MESSAGE_TYPES;

    if (array_key_exists($msgtype, $TOTARA_MESSAGE_TYPES)) {
        $text = get_string($TOTARA_MESSAGE_TYPES[$msgtype], 'totara_message');
    } else {
        $text = get_string('unknown', 'totara_message');
    }

    return ['text' => $text, 'icon' => ''];
}


/**
 * Get the eventdata for a given event type
 * @param int           $id       - message id
 * @param string        $event    - event type, either only if it is 'onaccept', 'onreject' or 'oninfo'
 * @param stdClass|null $metadata - allready read metadata record
 *
 * @return stdClass|null Returning null when unserialise process has a problem or the metadata record does
 *                       not have the properties, otherwise a dummy record.
 */
function totara_message_eventdata(int $id, string $event, ?stdClass $metadata = null): ?stdClass {
    global $DB;

    if (empty($metadata)) {
        // Enforcing the $metadata record to be required.
        debugging(
            "The third parameter of the function is now a part of requirement, " .
            "please provide the record for the accurate record lookup",
            DEBUG_DEVELOPER
        );

        // Processor's id default it to totara task, because only totara_task is having accept/reject and info
        // event data embedded into message metadata record.
        $processor_id = $DB->get_field('message_processors', 'id', ['name' => 'totara_task'], MUST_EXIST);
        $metadata = $DB->get_record(
            'message_metadata',
            [
                'notificationid' => $id,
                'processorid' => $processor_id,
            ],
            '*',
            MUST_EXIST
        );
    }

    if (!in_array($event, ['onaccept', 'onreject', 'oninfo'], true)) {
        // Default back to 'onreject' for backward compatible.
        debugging(
            "Invalid value of event type, default to 'onreject'",
            DEBUG_DEVELOPER
        );

        $event = 'onreject';
    }

    $metadata_attributes = get_object_vars($metadata);
    if (isset($metadata_attributes[$event])) {
        $event_value = $metadata_attributes[$event];
        $result = unserialize($event_value);
        return (false === $result || !is_object($result)) ? null : $result;
    }

    return null;
}


/**
 * construct the dismiss action in a new dialog
 *
 * @param int         $id message Id
 * @param string|null $processor_type
 *
 * @return string HTML of dismiss button
 */
function totara_message_dismiss_action(int $id, ?string $processor_type = null): string {
    global $FULLME, $PAGE, $OUTPUT;

    $clean_fullme = clean_param($FULLME, PARAM_LOCALURL);
    // Button Lang Strings
    $PAGE->requires->string_for_js('cancel', 'moodle');
    $PAGE->requires->string_for_js('dismiss', 'totara_message');
    $PAGE->requires->string_for_js('dismiss', 'block_totara_alerts');

    // Include JS for generic dismiss dialog
    $args = [
        'id' => $id,
        'selector' => 'dismissmsg',
        'clean_fullme' => $clean_fullme,
        'sesskey' => sesskey(),
        'extrabuttonjson' => null,
        'processor_type' => $processor_type,
    ];

    $PAGE->requires->js_init_call('M.totara_message.create_dialog', $args);

    // TODO SCANMSG: Check this still outputs required markup in no/script render
    // Construct HTML for dismiss button
    $str = get_string('dismiss', 'block_totara_alerts');
    $deleteicon = $OUTPUT->flex_icon('delete-disabled', ['alt' => $str, 'classes' => 'ft-size-300']);
    $out = html_writer::tag(
        'a',
        $deleteicon,
        [
            'href' => '#',
            'id' => 'dismissmsg' . $id . '-dialog',
            'name' => 'tm_dismiss_msg',
        ]
    );

    $out .= html_writer::tag(
        'noscript',
        html_writer::tag(
            'form',
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'returnto', 'value' => $clean_fullme]) .
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'image',
                    'class' => 'iconsmall',
                    'src' => $OUTPUT->image_url('t/delete_grey', 'totara_core'),
                    'title' => $str, 'alt' => $str,
                ]
            ),
            [
                'action' => new moodle_url(
                    '/totara/message/dismiss.php',
                    ['id' => $id, 'processor_type' => $processor_type]
                ),
                'method' => 'post',
            ]
        )
    );
    return $out;
}


/**
 * construct the dismiss action in a new dialog
 *
 * @param int             $id             A message notification's Id
 * @param stdClass[]|null $extrabuttons   Extra dialog buttons
 * @param string          $messagetype    Message type
 * @param string|null     $processor_type Whether it is a totara_task or totara_alert, by default we will set it to
 *                                        totara_alert.
 */
function totara_message_alert_popup(int $id, ?array $extrabuttons, string $messagetype, ?string $processor_type = null) {
    global $CFG, $FULLME, $PAGE, $DB;
    if (empty($processor_type)) {
        $processor_type = 'totara_alert';
    }

    if (!in_array($processor_type, ['totara_alert', 'totara_task'])) {
        throw new coding_exception("Invalid value for argument '\$processor_type'");
    }

    $clean_fullme = clean_param($FULLME, PARAM_LOCALURL);

    // Button Lang Strings
    $PAGE->requires->string_for_js('cancel', 'moodle');
    $PAGE->requires->string_for_js('dismiss', 'totara_message');
    $PAGE->requires->string_for_js('reviewitems', 'block_totara_alerts');

    // We can only assume that that this is for totara_alert popup block
    $metadata = $DB->get_record(
        'message_metadata',
        [
            'notificationid' => $id,
            'processorid' => $DB->get_field('message_processors', 'id', ['name' => $processor_type]),
        ],
        '*',
        MUST_EXIST
    );

    $eventdata = totara_message_eventdata($id, 'onaccept', $metadata);
    if ($eventdata && isset($eventdata->action)) {
        switch ($eventdata->action) {
            case 'facetoface':
                // Note that seminarevent may not exist if it has been deleted in the meantime.
                $seminarevent = \mod_facetoface\seminar_event::seek($eventdata->data['session']->id);
                $canbook = ($seminarevent->get_id() && ($seminarevent->has_capacity() || $seminarevent->get_allowoverbook()));
                if (!$canbook) {
                    // Remove accept / reject buttons
                    $extrabuttons = [];
                }
                break;
            case 'prog_extension':
                require_once($CFG->dirroot . '/totara/program/lib.php');
                if (empty($CFG->enableprogramextensionrequests) || !totara_prog_extension_allowed($eventdata->data['programid'])) {
                    // Remove Grant/Deny/Manage extension requests buttons.
                    $extrabuttons = [];
                }
                break;
        }
    }

    $extrabuttonjson = '';
    if ($extrabuttons) {
        $extrabuttonjson .= '{';
        $count = sizeof($extrabuttons);
        for ($i = 0; $i < $count; $i++) {
            $clean_redirect = clean_param($extrabuttons[$i]->redirect, PARAM_LOCALURL);
            $extrabuttonjson .= '"' . $extrabuttons[$i]->text . '":{"action":"' . $extrabuttons[$i]->action . '&sesskey=' . sesskey() . '", "clean_redirect":"' . $clean_redirect . '"}';
            $extrabuttonjson .= ($i < $count - 1) ? ',' : '';
        }
        $extrabuttonjson .= '}';
    }

    $args = [
        'id' => $id,
        'selector' => $messagetype,
        'clean_fullme' => $clean_fullme,
        'sesskey' => sesskey(),
        'extrabuttonjson' => $extrabuttonjson,
        'processor_type' => $processor_type,
    ];

    $PAGE->requires->js_init_call('M.totara_message.create_dialog', $args);
}


/**
 * checkbox all/none script
 */
function totara_message_checkbox_all_none(): void {
    global $PAGE;
    $PAGE->requires->strings_for_js(['all', 'none'], 'moodle');
    $PAGE->requires->js_init_call('M.totara_message.select_all_none_checkbox');
}


/**
 * include action buttons in a new dialog
 *
 * @param string      $action action to perform
 * @param string|null $processor_type
 *
 * @return void
 */
function totara_message_action_button(string $action, ?string $processor_type = null): void {
    global $FULLME, $PAGE;

    $clean_fullme = clean_param($FULLME, PARAM_LOCALURL);
    // Button Lang Strings
    $str = get_string($action, 'totara_message');
    $PAGE->requires->string_for_js('cancel', 'moodle');

    $args = [
        'action' => $action,
        'action_str' => $str,
        'clean_fullme' => $clean_fullme,
        'sesskey' => sesskey(),
        'processor_type' => $processor_type,
    ];

    $PAGE->requires->js_init_call('M.totara_message.create_action_dialog', $args);
}

/**
 * Migrate a record where the field 'messageid' is not null, and the field 'messagereadid' is null.
 *
 * @param int  $old_message_id  This could be the unread message's id or the read message's id. Depending on
 *                              the flag $is_read whether it is false/true. If it is FALSE, meaning that the
 *                              argument is representing for the unread message, and vice versa for TRUE.
 * @param int  $notification_id
 * @param bool $is_read
 * @return void
 */
function totara_message_migrate_message_metadata_to_notification(int $old_message_id, int $notification_id, bool $is_read): void {
    $processor_ids = builder::table('message_processors')->get()->pluck('id');

    foreach ($processor_ids as $processor_id) {
        $builder = builder::table(message_metadata::TABLE, 'mm');

        if (!$is_read) {
            // Working messages.
            $builder->join(['message_working', 'mw'], 'mm.messageid', 'mw.unreadmessageid');
            $builder->select('mm.*');

            $builder->where('mw.processorid', $processor_id);
            $builder->where('mm.messageid', $old_message_id);

        } else {
            // Finish read messages.
            $builder->where('mm.messagereadid', $old_message_id);
            $builder->where('mm.processorid', $processor_id);
        }

        $builder->map_to(message_metadata::class);

        /** @var message_metadata $message_metadata */
        $message_metadata = $builder->one();

        if (null === $message_metadata) {
            continue;
        }

        // Update the message id to null, and then update the notification's id to a new record.
        $message_metadata->notificationid = $notification_id;

        if (!$is_read) {
            $message_metadata->messageid = null;
        } else {
            $message_metadata->messagereadid = null;

            // We are marking the timeread too.
            $message_metadata->timeread = time();
        }

        $message_metadata->save();
    }
}