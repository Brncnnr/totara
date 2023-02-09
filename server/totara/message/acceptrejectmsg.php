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

/**
 * Page containing column display options, displayed inside show/hide popup dialog
 * Note that: WE CAN ONLY ASUME THAT THIS IS FOR TOTARA_TASK only.
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $USER;

require_once($CFG->dirroot . '/message/lib.php');
require_once($CFG->dirroot . '/totara/message/lib.php');

$id = required_param('id', PARAM_INT);
$event = required_param('event', PARAM_RAW);

require_login();

if (isguestuser()) {
    redirect($CFG->wwwroot);
}

if (!in_array($event, ['onaccept', 'onreject'])) {
    print_error('error:invalideventtype', 'totara_message', '', $event);
}

$metadata = $DB->get_record(
    'message_metadata',
    [
        'notificationid' => $id,
        'processorid' => $DB->get_field('message_processors', 'id', ['name' => 'totara_task'], MUST_EXIST),
    ]
);
$eventdata = totara_message_eventdata($id, $event, $metadata);
$msgtext = isset($eventdata->text) ? $eventdata->text : '';

// check message ownership
$msg = $DB->get_record('notifications', ['id' => $id]);
if (!$msg || $msg->useridto != $USER->id || !confirm_sesskey()) {
    print_error('notyours', 'totara_message', $id);
}

$display = totara_message_msgtype_text($metadata->msgtype);
$type = $display['icon'];
$subject = format_string($msg->subject);
$type_alt = $display['text'];

if ($msg->useridfrom == 0) {
    $from = core_user::get_support_user();
} else {
    $from = $DB->get_record('user', ['id' => $msg->useridfrom]);
}
$fromname = fullname($from) . " (" . clean_string($from->email) . ")";

$tab = new html_table();
$tab->attributes['class'] = 'fullwidth invisiblepadded';
$tab->data = [];

print html_writer::start_tag('div', ['id' => 'totara-msgs-action']);

$cell = new html_table_cell($msgtext);
$cell->attributes['colspan'] = '2';
$tab->data[] = new html_table_row([$cell]);
$cell = new html_table_cell('&nbsp;');
$cell->attributes['colspan'] = '2';
$tab->data[] = new html_table_row([$cell]);
$cells = [];
$cell = new html_table_cell(html_writer::tag('label', get_string('subject', 'forum'), ['for' => 'dismiss-type']));
$cell->attributes['class'] = 'totara-msgs-action-left';
$cell = new html_table_cell(html_writer::tag('div', $subject, ['id' => 'dismiss-type']));
$cell->attributes['class'] = 'totara-msgs-action-right';
$cells [] = $cell;
$tab->data[] = new html_table_row($cells);
$icon = html_writer::empty_tag(
    'img',
    [
        'src' => totara_msg_icon_url($metadata->icon),
        'class' => 'msgicon',
        'alt' => format_string($msg->subject),
        'title' => format_string($msg->subject)
    ]
);

$cells = [];
$cell = new html_table_cell(html_writer::tag('label', get_string('type', 'block_totara_alerts'), ['for' => 'dismiss-type']));
$cell->attributes['class'] = 'totara-msgs-action-left';
$cell = new html_table_cell(html_writer::tag('div', $icon, ['id' => 'dismiss-type']));
$cell->attributes['class'] = 'totara-msgs-action-right';
$cells [] = $cell;
$tab->data[] = new html_table_row($cells);
$cells = [];
$cell = new html_table_cell(html_writer::tag('label', get_string('from', 'block_totara_alerts'), ['for' => 'dismiss-from']));
$cell->attributes['class'] = 'totara-msgs-action-left';
$cell = new html_table_cell(html_writer::tag('div', $fromname, ['id' => 'dismiss-from']));
$cell->attributes['class'] = 'totara-msgs-action-right';
$cells [] = $cell;
$tab->data[] = new html_table_row($cells);

$cells = [];
$cell = new html_table_cell(
    html_writer::tag('label', get_string('statement', 'block_totara_alerts'), ['for' => 'dismiss-statement'])
);

$cell->attributes['class'] = 'totara-msgs-action-left';
$cell = new html_table_cell(html_writer::tag('div', $msg->fullmessage, ['id' => 'dismiss-statement']));
$cell->attributes['class'] = 'totara-msgs-action-right';
$cells [] = $cell;
$tab->data[] = new html_table_row($cells);

print html_writer::table($tab);
print html_writer::end_tag('div');
