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
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

use totara_core\advanced_feature;
use totara_engage\access\access_manager;
use totara_engage\query\query;
use totara_topic\provider\topic_provider;

require_once(__DIR__ . '/../../config.php');
global $USER, $OUTPUT, $PAGE;

require_login();
advanced_feature::require('engage_resources');
access_manager::require_library_capability();

$title = get_string('yourresources', 'totara_engage');

// Set page properties.
$PAGE->set_context(\context_user::instance($USER->id));
$PAGE->set_title($title);
$PAGE->set_pagelayout('legacynolayout');
$PAGE->set_url(new moodle_url('/totara/engage/your_resources.php'));

$query = new query();
$query->set_component('totara_engage');
$query->set_area('owned');

$topics = [['id' => null, 'label' => get_string('all', 'core')]];
foreach (topic_provider::get_all() as $topic) {
    $topics[] = ['id' => $topic->get_id(), 'value' => $topic->get_id(), 'label' => $topic->get_display_name(false)];
}

$filters = [
    'access'  => [
        'label' => get_string('access', 'totara_engage'),
        'options' => $query->get_filter_options('ACCESS')
    ],
    'type'    => [
        'label' => get_string('filtertype', 'totara_engage'),
        'options' => $query->get_filter_options('TYPE')
    ],
    'section' => [
        'label' => get_string('filtersection', 'totara_engage'),
        'options' => $query->get_filter_options('SECTION')
    ],
    'sort'    => [
        'sort' => get_string('sort', 'totara_engage'),
        'options' => $query->get_filter_options('SORT')
    ],
    'topic'   => [
        'label' => get_string('topic', 'totara_engage'),
        'options' => $topics
    ],
];
$tui = new \totara_tui\output\component(
    'totara_engage/pages/LibraryView',
    [
        'id' => 'yourresources',
        'title' => $title,
        'content' => [
            'component' => 'YourResourcesContent',
            'tuicomponent' => 'totara_engage/components/contribution/YourResources',
        ],
        'page-props' => [
            'showNotification' => false,
            'filters' => $filters,
        ],
    ]
);
$tui->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->render($tui);
echo $OUTPUT->footer();