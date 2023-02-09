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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use totara_notification\exception\notification_exception;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\local\helper;
use totara_tui\output\component;

global $CFG, $OUTPUT, $PAGE, $DB, $USER;

require_once(__DIR__ . '/../../config.php');

// Get URL parameters
$context_id = required_param('context_id', PARAM_INT);
$component = optional_param('component', extended_context::NATURAL_CONTEXT_COMPONENT, PARAM_TEXT);
$area = optional_param('area', extended_context::NATURAL_CONTEXT_AREA, PARAM_TEXT);
$item_id = optional_param('item_id', extended_context::NATURAL_CONTEXT_ITEM_ID, PARAM_INT);

$extended_context = extended_context::make_with_id($context_id, $component, $area, $item_id);
$context = $extended_context->get_context();

// Check the context level
if ($context->contextlevel === CONTEXT_SYSTEM) {
    // If it is under the context system, we will redirect the user to the admin page
    // rather than use this page. Because this page must only be used for lower context purpose.
    // Note: in the future we might want to do sort of component,area and instance id check as well
    redirect(new moodle_url("/totara/notification/notifications.php"));
} else if ($context->contextlevel === CONTEXT_COURSE) {
    /** @var \context_course $context */
    $course = get_course($context->instanceid);
    $cm = null;
} else if ($context->contextlevel === CONTEXT_MODULE) {
    /** @var \context_module $context */
    [$course, $cm] = get_course_and_cm_from_cmid($context->instanceid);
} else {
    throw notification_exception::on_manage();
}

require_login($course, true, $cm);

if (!notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($extended_context)) {
    throw notification_exception::on_manage();
}

$params = ['context_id' => $extended_context->get_context_id()];
$url = new moodle_url("/totara/notification/notification_preference.php", $params);

if (!$extended_context->is_natural_context()) {
    $url->params([
        'component' => $extended_context->get_component(),
        'area' => $extended_context->get_area(),
        'item_id' => $extended_context->get_item_id(),
    ]);
}

$PAGE->set_url($url);

$title = get_string('notifications', 'totara_notification');
$PAGE->set_title($title);

$PAGE->set_pagelayout('admin');

$tui = new component(
    'totara_notification/pages/NotificationPage',
    [
        'title' => $title,
        'context-id' => $extended_context->get_context_id(),
        'preferred-editor-format' => helper::get_preferred_editor_format(FORMAT_JSON_EDITOR),
        'extended-context' => [
            'component' => $extended_context->get_component(),
            'area' => $extended_context->get_area(),
            'itemId' => $extended_context->get_item_id(),
        ],
    ]
);

$tui->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->render($tui);
echo $OUTPUT->footer();