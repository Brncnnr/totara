<?php
/*
 * This file is part of Totara LMS
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_program
 */

/**
 * Program notification view page
 */
use totara_core\extended_context;
use totara_tui\output\component;
use totara_notification\local\helper;
use totara_program\event\program_viewed;

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

global $OUTPUT, $PAGE;

// Get URL parameters
$context_id = required_param('context_id', PARAM_INT);
$id = required_param('id', PARAM_INT); // program id

require_login();

$program = new program($id);
$iscertif = $program->is_certif();

$programcontext = $program->get_context();

$extended_context = extended_context::make_with_context(
    $programcontext,
    $iscertif ? 'totara_certification' : 'totara_program',
    'program',
    $program->id
);

require_capability('totara/program:configuremessages', $programcontext);
$program->check_enabled();

$PAGE->set_url(
    new moodle_url('/totara/program/edit_notifications.php'),
    ['context_id' => $context_id, 'id' => $program->id]
);
$PAGE->set_program($program);
$PAGE->set_title($program->fullname);
$PAGE->set_heading($program->fullname);

// Trigger event.
$dataevent = array('id' => $program->id, 'other' => array('section' => 'messages'));
program_viewed::create_from_data($dataevent)->trigger();

// Display.
$heading = format_string($program->fullname);

if ($iscertif) {
    $heading = get_string('header:certification', 'totara_certification', $heading);
}

$tui = new component(
    'totara_notification/pages/NotificationPage',
    [
        'title'                                => get_string('notifications', 'totara_notification'),
        'context-id'                           => $extended_context->get_context_id(),
        'extendedContext'                      => [
            'component' => $extended_context->get_component(),
            'area'      => $extended_context->get_area(),
            'itemId'    => $extended_context->get_item_id(),
        ],
        'can-change-delivery-channel-defaults' => false,
        'preferred-editor-format'              => helper::get_preferred_editor_format(FORMAT_JSON_EDITOR),
    ]
);
$tui->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

// Display the current status
echo $program->display_current_status();
$exceptions = $program->get_exception_count();
$currenttab = 'notifications';
require('tabs.php');

echo $OUTPUT->render($tui);
echo $OUTPUT->footer();
