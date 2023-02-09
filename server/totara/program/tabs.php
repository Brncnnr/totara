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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage program
 */

use totara_core\advanced_feature;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\interactor\notification_audit_interactor;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\local\helper;
use totara_core\extended_context;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

global $USER, $DB;

$id = optional_param('id', 0, PARAM_INT);
$edit = optional_param('edit', 'off', PARAM_TEXT);
$iscertif = optional_param('iscertif', 0, PARAM_BOOL);
if ($id) {
    $iscertif = ($DB->get_field('prog', 'certifid', array('id' => $id)) ? 1 : 0);
}

if (!isset($currenttab)) {
    $currenttab = 'details';
}

if (isset($programcontext)) {
    $context = $programcontext;
} else if (isset($program)) {
    $context = $program->get_context();
} else if (isset($systemcontext)) {
    $context = $systemcontext;
} else {
    $context = context_system::instance();
}

$toprow = array();
$secondrow = array();
$activated = array();
$inactive = array();

// Overview Tab
$toprow[] = new tabobject('overview', $CFG->wwwroot.'/totara/program/edit.php?id='.$id, get_string('overview', 'totara_program'));
if (substr($currenttab, 0, 7) == 'overview'){
    $activated[] = 'overview';
}

// Details Tab
if (has_capability('totara/program:configuredetails', $context)) {
    //disable details link if creating a new program to avoid fatal error
    $url = ($id == 0) ? '#' : $CFG->wwwroot.'/totara/program/edit.php?id='.$id.'&amp;action=edit';
    $toprow[] = new tabobject('details', $url, get_string('details', 'totara_program'));
    if (substr($currenttab, 0, 7) == 'details'){
        $activated[] = 'details';
    }
}

// Content Tab
if (has_capability('totara/program:configurecontent', $context)) {
    $toprow[] = new tabobject('content', $CFG->wwwroot.'/totara/program/edit_content.php?id='.$id, get_string('content', 'totara_program'));
    if (substr($currenttab, 0, 7) == 'content'){
        $activated[] = 'content';
    }
}

// Assignments Tab
if (has_capability('totara/program:configureassignments', $context)) {
    $toprow[] = new tabobject('assignments', $CFG->wwwroot.'/totara/program/edit_assignments.php?id='.$id, get_string('assignments', 'totara_program'));
    if (substr($currenttab, 0, 11) == 'assignments'){
        $activated[] = 'assignments';
    }
}
// Messages Tab
if (has_capability('totara/program:configuremessages', $context)) {

    // Check the config setting to show program message tab.
    $show_program_message_tab = (!empty($CFG->show_program_message_tab) && $CFG->show_program_message_tab);
    $has_program_messages = $DB->record_exists('prog_message', [], IGNORE_MULTIPLE);

    if ($has_program_messages || $show_program_message_tab) {
        $toprow[] = new tabobject('messages', $CFG->wwwroot . '/totara/program/edit_messages.php?id=' . $id, get_string('messages', 'totara_program'));
        if (substr($currenttab, 0, 8) == 'messages') {
            $activated[] = 'messages';
        }
    }
}

// Notification Tab
if (has_capability('totara/program:configuremessages', $context)) {
    $enable_notification_tab = false;

    if ($id) {
        $extended_context = extended_context::make_with_context(
            $context,
            $iscertif ? 'totara_certification' : 'totara_program',
            'program',
            $id
        );

        // Check if the user has any of the capabilities provided by the integrated plugins or not.
        $enable_notification_tab = notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($extended_context);
    }

    if ($enable_notification_tab) {
        $resolver_classes = notifiable_event_resolver_factory::get_resolver_classes();
        $pref_interactor = new notification_preference_interactor($extended_context, $USER->id);
        $audit_interactor = new notification_audit_interactor($extended_context, $USER->id);

        $filtered_resolvers = array_filter(
            $resolver_classes,
            function ($resolver_class) use ($pref_interactor, $audit_interactor, $extended_context): bool {
                /**
                 * @see notifiable_event_resolver::supports_context()
                 * @var bool $support
                 */
                $support = call_user_func([$resolver_class, 'supports_context'], $extended_context);
                if (!$support) {
                    return false;
                }

                // If any ancestor context is disabled then we won't show the resolver here.
                $parent_extended_context = $extended_context->get_parent();
                if (helper::is_resolver_disabled_by_any_context(
                    $resolver_class,
                    $parent_extended_context
                )) {
                    return false;
                }

                // We are only displaying the event resolver if the user has the capability to
                // manage/interact with the resolver.
                return $pref_interactor->can_manage_notification_preferences_of_resolver($resolver_class)
                    || $audit_interactor->can_audit_notifications_of_resolver($resolver_class);
            }
        );

        // Hide tab if there are no resolvers the user can see
        $enable_notification_tab = count($filtered_resolvers) > 0;

        if (substr($currenttab, 0, 13) == 'notifications') {
            $activated[] = 'notifications';
        }
    } else {
        $inactive[] = 'notifications';
    }

    $url = new moodle_url(
        '/totara/program/edit_notifications.php',
        ['context_id' => $context->id, 'id' => $id]
    );
    $toprow[] = new tabobject('notifications', $url, get_string('notifications', 'totara_program'));
}

// Certification Tab
if ($iscertif && has_capability('totara/certification:configurecertification', $context)
    && advanced_feature::is_enabled('certifications')) {
    $toprow[] = new tabobject('certification', $CFG->wwwroot.'/totara/certification/edit_certification.php?id='.$id,
                    get_string('certification', 'totara_certification'));
    if (substr($currenttab, 0, 13) == 'certification') {
        $activated[] = 'certification';
    }
}

if (!empty($CFG->enableprogramcompletioneditor) &&
    has_capability('totara/program:editcompletion', $context)) {
    $toprow[] = new tabobject('completion', $CFG->wwwroot.'/totara/program/completion.php?id='.$id,
        get_string('completion', 'totara_program'));
    if (substr($currenttab, 0, 10) == 'completion') {
        $activated[] = 'completion';
    }
}

// Exceptions Report Tab
// Only show if there are exceptions or you are on the exceptions tab already
if (has_capability('totara/program:handleexceptions', $context)) {
    $exceptioncount = $exceptions ? $exceptions : '0';
    $toprow[] = new tabobject('exceptions', $CFG->wwwroot.'/totara/program/exceptions.php?id='.$id, get_string('exceptions', 'totara_program', $exceptioncount));
    if (substr($currenttab, 0, 10) == 'exceptions'){
        $activated[] = 'exceptions';
    } else if (!($exceptions || (substr($currenttab, 0, 10) == 'exceptions'))) {
        $inactive[] = 'exceptions';
    }
}

if (!$id) {
    $inactive = array_merge($inactive, array('overview', 'content', 'assignments', 'messages', 'notifications', 'certification', 'completion'));
}

$tabs = array($toprow);

// print out tabs
print_tabs($tabs, $currenttab, $inactive, $activated);
