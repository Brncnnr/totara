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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */

use core\notification;
use core_container\factory;
use container_workspace\event\workspace_viewed;
use container_workspace\workspace;
use container_workspace\tracker\tracker;
use container_workspace\totara\menu\your_spaces;
use container_workspace\loader\member\loader;
use container_workspace\query\member\sort as member_sort;
use container_workspace\query\discussion\sort as discussion_sort;
use core\output\notification as output_notification;
use totara_core\advanced_feature;
use container_workspace\interactor\workspace\interactor;
use totara_tui\output\component;
use container_workspace\totara\menu\find_spaces;

require_once(__DIR__ . "/../../../config.php");
global $OUTPUT, $PAGE, $USER;

// Note: This is intentional to not use the $id for function `require_login` as it will redirect user to the enrol page.
require_login();

$id = required_param('id', PARAM_INT);
$member_sort = optional_param('member_sort', member_sort::NAME, PARAM_INT);
$discussion_sort = optional_param('discussion_sort', discussion_sort::RECENT, PARAM_INT);
$tab = optional_param('tab', null, PARAM_ALPHA);

// Get the flag of notification to check the notification is fired or not.
$hold_notification = optional_param('hold_notification', false, PARAM_BOOL);

try {
    /** @var workspace $workspace */
    $workspace = factory::from_id($id);

    if (!$workspace->is_typeof(workspace::get_type())) {
        throw new \coding_exception("Invalid type of container");
    }

    advanced_feature::require('container_workspace');

    $interactor = new interactor($workspace);
    $context = $workspace->get_context();

    $PAGE->set_url($workspace->get_view_url());
    $PAGE->set_context($context);

    if ($interactor->is_joined()) {
        $PAGE->set_totara_menu_selected(your_spaces::class);
    } else {
        $PAGE->set_totara_menu_selected(find_spaces::class);
    }

    if ($interactor->can_view_workspace()) {
        $PAGE->set_title(format_string($workspace->fullname));
    } else {
        $PAGE->set_title(get_string('error:view_workspace', 'container_workspace'));
    }

    if ($workspace->is_to_be_deleted()) {
        $PAGE->set_title(get_string('error:not_found', 'container_workspace'));
    }

    $PAGE->set_pagelayout('legacynolayout');

    $workspace_id = $workspace->get_id();
    $member = loader::get_for_user($USER->id, $workspace_id);

    if (null !== $member && $member->is_active()) {
        // User is a member of this workspace, therefore we should track the workspace for user.
        $tracker = new tracker($USER->id);
        $tracker->visit_workspace($workspace);
    }

    if (!member_sort::is_valid($member_sort)) {
        $member_sort = member_sort::NAME;
    }

    if (!discussion_sort::is_valid($discussion_sort)) {
        $discussion_sort = discussion_sort::is_valid($discussion_sort);
    }

    $tui = new component(
        'container_workspace/pages/WorkspaceEmptyPage',
        ['not-found' => $workspace->is_to_be_deleted()]
    );
    $interactor = new interactor($workspace);

    if ($interactor->can_view_workspace()) {
        $parameters = [
            'workspace-id' => $workspace->get_id(),
            'member-sort-option' => member_sort::get_code($member_sort),
            'discussion-sort-option' => discussion_sort::get_code($discussion_sort),
            'show-library-tab' => advanced_feature::is_enabled('engage_resources'),
        ];

        if (null !== $tab && in_array($tab, ['library', 'discussion', 'members', 'audiences'])) {
            // Processing on tab.
            if ('members' === $tab && !$interactor->can_view_members()) {
                // Nope, user cannot see member tab - make it to discussions.
                $tab = 'discussion';
            } else if ('library' === $tab && !$interactor->can_view_library()) {
                // Nope, user cannot see library tab - make it to discussions.
                $tab = 'discussion';
            } else if ('audiences' === $tab && !$interactor->can_add_audiences()) {
                // Nope, user cannot see audiences tab - make it to discussions.
                $tab = 'discussion';
            }

            $parameters['selected-tab'] = $tab;
        }

        $tui = new component(
            'container_workspace/pages/WorkspacePage',
            $parameters
        );

        // We only want to count views if access is allowed.
        $workspace_viewed = workspace_viewed::from_workspace($workspace);
        $workspace_viewed->trigger();
    }
} catch (dml_missing_record_exception $e) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/container/type/workspace/workspace.php', ['id' => $id]);
    $PAGE->set_title(get_string('error:workspace_not_found', 'container_workspace'));
    $tui = new component(
        'container_workspace/pages/WorkspaceEmptyPage',
        ['not-found' => true, 'type' => 'workspace']
    );
}

$tui->register($PAGE);
$notifications = [];
if ($hold_notification) {
    $notifications = notification::fetch();
}

echo $OUTPUT->header();
echo $OUTPUT->render($tui);
echo $OUTPUT->footer();

if ($hold_notification) {
    // Only add the notification at the very end of the process, as we can skip it from being
    // printed to the head of the page.

    /** @var output_notification $notification */
    foreach ($notifications as $notification) {
        notification::add($notification->get_message(), $notification->get_message_type());
    }
}