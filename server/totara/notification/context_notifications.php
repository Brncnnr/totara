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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author  Alvin Smith <alvin.smith@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\local\helper;
use totara_tui\output\component;

global $CFG, $OUTPUT, $PAGE, $USER;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Get URL parameters
$context_id = required_param('context_id', PARAM_INT);
$component = optional_param('component', extended_context::NATURAL_CONTEXT_COMPONENT, PARAM_TEXT);
$area = optional_param('area', extended_context::NATURAL_CONTEXT_AREA, PARAM_TEXT);
$item_id = optional_param('item_id', extended_context::NATURAL_CONTEXT_ITEM_ID, PARAM_INT);

$extended_context = extended_context::make_with_id($context_id, $component, $area, $item_id);
$context = $extended_context->get_context();

// TODO: TL-33115 sort out responsibility between this script and notification_preference.php
if (CONTEXT_SYSTEM == $context->contextlevel) {
    // If it is under the context system, we will redirect the user to the admin page
    // rather than use this page. Because this page must only be used for lower context purpose.
    // Note: in the future we might want to do sort of component,area and instance id check as well
    redirect(new moodle_url("/totara/notification/notifications.php"));
}
if (CONTEXT_COURSE == $context->contextlevel || CONTEXT_MODULE == $context->contextlevel) {
    redirect(new moodle_url("/totara/notification/notification_preference.php",
        [
            'context_id' => $context_id,
            'component' => $component,
            'area' => $area,
            'item_id' => $item_id,
        ]
    ));
}

require_login();
$interactor = new notification_preference_interactor($extended_context, $USER->id);

if (!$interactor->can_manage_notification_preferences()) {
    throw notification_exception::on_manage();
}

$PAGE->set_context($context);

$params = ['context_id' => $extended_context->get_context_id()];
$url = new moodle_url("/totara/notification/context_notifications.php", $params);
if (!$extended_context->is_natural_context()) {
    $url->params([
        'component' => $extended_context->get_component(),
        'area' => $extended_context->get_area(),
        'item_id' => $extended_context->get_item_id(),
    ]);
}
$PAGE->set_url($url);

$PAGE->set_pagelayout('noblocks');

$tui = new component(
    'totara_notification/pages/NotificationPage',
    [
        'title' => get_string('notifications', 'totara_notification'),
        'context-id' => $extended_context->get_context_id(),
        'extended-context' => [
            'component' => $extended_context->get_component(),
            'area' => $extended_context->get_area(),
            'itemId' => $extended_context->get_item_id(),
        ],
        'preferred-editor-format' => helper::get_preferred_editor_format(FORMAT_JSON_EDITOR),
    ]
);

$tui->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->render($tui);
echo $OUTPUT->footer();