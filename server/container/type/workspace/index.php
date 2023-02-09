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
use container_workspace\query\workspace\query;
use container_workspace\loader\workspace\loader;
use container_workspace\workspace;
use container_workspace\tracker\tracker;
use core_container\factory;
use container_workspace\totara\menu\your_spaces;
use core\notification;
use core\output\notification as output_notification;
use totara_core\advanced_feature;
use totara_tui\output\component;

require_once(__DIR__ . "/../../../config.php");
require_login();
advanced_feature::require('container_workspace');

global $PAGE, $OUTPUT, $USER;

// Get the flag of notification to check the notification is fired or not.
$hold_notification = optional_param('hold_notification', false, PARAM_BOOL);

$tracker = new tracker();
$workspace_id = $tracker->get_last_visit_workspace();

if (null !== $workspace_id && 0 !== $workspace_id) {
    $workspace = factory::from_id($workspace_id);

    if (!$workspace->is_typeof(workspace::get_type())) {
        // This should never be happening. But it is for false safety when the tracker has some issues with fetching
        // the record(s) that are not the workspace.
        throw new \coding_exception("Cannot find the workspace");
    }

    $url = $workspace->get_view_url();

    if ($hold_notification) {
        $url->param('hold_notification', $hold_notification);
    }

    redirect($url);
    // Should die here.
}

$query = query::create_for_user();

$paginator = loader::get_workspaces($query);
$total = $paginator->get_total();

if (null !== $total && 0 < $total) {
    // User does have the workspace, we just need to navigate it to the first workspace.
    /** @var workspace $workspace */
    $workspace = $paginator->get_items()->first();

    $url = $workspace->get_view_url();
    if ($hold_notification) {
        $url->param('hold_notification', $hold_notification);
    }

    redirect($url);
}

// Righty, no workspace. time to render an empty page. We use category context here.
$category_id = workspace::get_default_category_id();
$context = \context_coursecat::instance($category_id);

$PAGE->set_context($context);
$PAGE->set_title(get_string('spaces', 'container_workspace'));

$PAGE->set_pagelayout('legacynolayout');
$PAGE->set_url(new \moodle_url("/container/type/workspace/index.php"));
$PAGE->set_totara_menu_selected(your_spaces::class);

$notifications = [];
if ($hold_notification) {
    $notifications = notification::fetch();
}

$context = context_user::instance($USER->id);
if (!has_capability('container/workspace:workspacesview', $context, $USER->id)) {
    $PAGE->set_title(get_string('error:view_workspace', 'container_workspace'));
    $component = new component('container_workspace/pages/WorkspaceEmptyPage');
} else {
    $props = [
        'show-recommended' => advanced_feature::is_enabled('ml_recommender'),
    ];

    $component = new component(
        'container_workspace/pages/EmptySpacesPage',
        $props
    );
}

$component->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->render($component);
echo $OUTPUT->footer();

if ($hold_notification) {
    // Only add the notification at the very end of the process, as we can skip it from being
    // printed to the head of the page.

    /** @var output_notification $notification */
    foreach ($notifications as $notification) {
        notification::add($notification->get_message(), $notification->get_message_type());
    }
}