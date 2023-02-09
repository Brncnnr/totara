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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara
 * @subpackage message
 */

/**
 * Page containing column display options, displayed inside show/hide popup dialog
 */

require_once(__DIR__ . '/../../config.php');
global $DB, $CFG, $PAGE, $USER;

require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/totara/message/lib.php');
require_once($CFG->dirroot.'/totara/core/lib.php');

$PAGE->set_context(context_system::instance());
require_login();

if (isguestuser()) {
    redirect($CFG->wwwroot);
}

$id = required_param('id', PARAM_INT);

// Whether it is a totara_task or totara_alert. By default, we default to totara_task.
$processor_type = optional_param('processor_type', 'totara_task', PARAM_AREA);
$msg = $DB->get_record('notifications', array('id' => $id));

if (!$msg || $msg->useridto != $USER->id || !confirm_sesskey()) {
    print_error('notyours', 'totara_message', $id);
}

$canbook = false;
$isfacetoface = false;
$isprogextensionallowed = true;
$metadata = $DB->get_record(
    'message_metadata',
    [
        'notificationid' => $id,
        'processorid' => $DB->get_field('message_processors', 'id', ['name' => $processor_type], MUST_EXIST)
    ]
);

$eventdata = totara_message_eventdata($id, 'onaccept', $metadata);
if ($eventdata && isset($eventdata->action)) {
    switch ($eventdata->action) {
        case 'facetoface':
            $isfacetoface = true;
            // Note that seminarevent may not exist if it has been deleted in the meantime.
            $seminarevent = \mod_facetoface\seminar_event::seek($eventdata->data['session']->id);
            $canbook = ($seminarevent->get_id() && ($seminarevent->has_capacity() || $seminarevent->get_allowoverbook()));
            break;
        case 'prog_extension':
            require_once($CFG->dirroot . '/totara/program/lib.php');
            $isprogextensionallowed = !empty($CFG->enableprogramextensionrequests) && totara_prog_extension_allowed($eventdata->data['programid']);
            break;
    }
}

$from     = totara_get_sender_from_user_by_id($msg->useridfrom);
$fromname = fullname($from) . " (" . clean_string($from->email) . ")";
$subject  = format_string($msg->subject);

if ($isfacetoface && !$DB->record_exists('facetoface_sessions', array('id' => $eventdata->data['session']->id))) {
        $subject .= ' (' . html_writer::tag('strong', get_string('f2fsessiondeleted', 'block_totara_tasks')) . ')';
} else if ($isfacetoface && !$canbook) {
        $subject .= ' (' . html_writer::tag('strong', get_string('f2fsessionfull', 'block_totara_tasks')) . ')';
}

// Add info to the subject to explain that program extension is not allowed.
if (!$isprogextensionallowed) {
    $subject .= ' (' . html_writer::tag('strong', get_string('allowextensionrequestsdisabled', 'totara_program')) . ')';
}

global $TOTARA_MESSAGE_TYPES;
$msgtype = get_string($TOTARA_MESSAGE_TYPES[$metadata->msgtype], 'totara_message');
$icon = $OUTPUT->pix_icon('msgicons/' . $metadata->icon, format_string($msgtype), 'totara_core', array('class' => 'msgicon',  'alt' => format_string($msgtype)));

echo html_writer::start_tag('div', array('id' => 'totara-msgs-dismiss'));
echo html_writer::start_tag('dl', array('class' => 'list'));

if (!empty($msg->subject)) {
    echo html_writer::tag('dt', get_string('subject', 'forum'));
    echo html_writer::tag('dd', $subject);
}
echo html_writer::tag('dt', get_string('type', 'block_totara_alerts'));
echo html_writer::tag('dd', $icon);

echo html_writer::tag('dt', get_string('from', 'block_totara_alerts'));
echo html_writer::tag('dd', $fromname);

echo html_writer::tag('dt', get_string('statement', 'block_totara_alerts'));
echo html_writer::tag('dd', $msg->fullmessagehtml);

if ($msg->contexturl && $msg->contexturlname) {
    echo html_writer::tag('dt', get_string('statement', 'block_totara_alerts'));
    echo html_writer::tag('dd', html_writer::tag('a', $msg->contexturlname, array('href' => $msg->contexturl)));
}
echo html_writer::end_tag('dl');

// Create input reason for declining/approving the request.
if ($eventdata && ($eventdata->action === 'plan' || ($eventdata->action === 'prog_extension' && $isprogextensionallowed))) {
    echo html_writer::tag('label', get_string('reasonfordecision', 'totara_message'), array('for' => 'dismiss-reasonfordecision'));
    echo html_writer::empty_tag('input', array('class' => 'reasonfordecision', 'type' => 'text', 'name' => 'reasonfordecision', 'id' => 'reasonfordecision', 'size' => '80', 'maxlength' => '255'));
}
echo html_writer::end_tag('div');
