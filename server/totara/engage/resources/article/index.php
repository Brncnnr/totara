<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package engage_article
 */

use engage_article\totara_engage\interactor\article_interactor;
use engage_article\totara_engage\resource\article;
use engage_article\event\article_viewed;
use totara_playlist\totara_engage\link\nav_helper;
use totara_tui\output\component;
use totara_core\advanced_feature;
use totara_engage\access\access_manager;
use core\notification;

require_once(__DIR__ . "/../../../../config.php");
global $OUTPUT, $PAGE, $USER;

require_login();
advanced_feature::require('engage_resources');

// {ttr_engage_resource}'s id
$id = required_param("id", PARAM_INT);
$source = optional_param('source', null, PARAM_TEXT);

/** @var article $resource */
$resource = article::from_resource_id($id);
$url = new \moodle_url("/totara/engage/resources/article/index.php", ['id' => $id]);

$PAGE->set_url($url);
$PAGE->set_pagelayout('legacynolayout');

$tui = null;
if (!$resource->is_available()) {
    $message = get_string('resource_unavailable', 'totara_engage');

    // Fallback to the context system.
    $PAGE->set_context(\context_system::instance());
    $PAGE->set_title($message);

    $tui = new component('totara_engage/pages/EngageUnavailableResource');
    $tui->register($PAGE);
} else if (access_manager::can_access($resource, $USER->id)) {
    $PAGE->set_context($resource->get_context());
    $PAGE->set_title($resource->get_name());

    // Build the back button
    [$back_button, $navigation_buttons] = nav_helper::build_resource_nav_buttons($resource->get_id(), $resource->get_userid(), $source);

    $tui = new component(
        'engage_article/pages/ArticleView',
        [
            'resource-id' => $id,
            'title' => $resource->get_name(),
            'back-button' => $back_button,
            'navigation-buttons' => $navigation_buttons,
            'interactor' => article_interactor::create_from_accessible($resource, $USER->id)->to_array(),
        ]
    );

    $tui->register($PAGE);

    $event = article_viewed::from_article($resource);
    $event->trigger();
} else {
    $PAGE->set_context(\context_system::instance());
}

echo $OUTPUT->header();

if (null !== $tui) {
    echo $OUTPUT->render($tui);
} else {
    notification::error(get_string('cannot_view_article', 'engage_article'));
}

echo $OUTPUT->footer();