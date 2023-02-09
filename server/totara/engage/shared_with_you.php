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

require_once(__DIR__ . '/../../config.php');
global $USER, $OUTPUT, $PAGE;

require_login();
advanced_feature::require('engage_resources');
access_manager::require_library_capability();

$title = get_string('sharedwithyou', 'totara_engage');

// Set page properties.
$PAGE->set_context(\context_user::instance($USER->id));
$PAGE->set_title($title);
$PAGE->set_pagelayout('legacynolayout');
$PAGE->set_url(new moodle_url('/totara/engage/shared_with_you.php'));
$PAGE->set_totara_menu_selected('\totara_engage\totara\menu\library');

$tui = new \totara_tui\output\component(
    'totara_engage/pages/LibraryView',
    [
        'id' => 'sharedwithyou',
        'title' => $title,
        'content' => [
            'component' => 'SharedWithYouContent',
            'tuicomponent' => 'totara_engage/components/contribution/SharedWithYou',
        ],
    ]
);
$tui->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->render($tui);
echo $OUTPUT->footer();