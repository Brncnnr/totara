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
use container_workspace\workspace;
use container_workspace\totara\menu\find_spaces;
use container_workspace\query\workspace\source;
use container_workspace\query\workspace\sort;
use totara_core\advanced_feature;
use container_workspace\query\workspace\access;
use totara_tui\output\component;

require_once(__DIR__ . "/../../../config.php");
global $PAGE, $OUTPUT, $USER;

require_login();
advanced_feature::require('container_workspace');

$sort = optional_param('source', sort::get_code(sort::RECENT), PARAM_ALPHA);
$source = optional_param('source', source::get_code(source::ALL), PARAM_ALPHA);
$access = optional_param('access', null, PARAM_ALPHA);
$search_term = optional_param('search_term', '', PARAM_TEXT);

$category_id = workspace::get_default_category_id();
$context = \context_coursecat::instance($category_id);

$PAGE->set_context($context);
$PAGE->set_url("/container/type/workspace/spaces.php");
$PAGE->set_title(get_string('find_spaces', 'container_workspace'));
$PAGE->set_pagelayout('legacynolayout');
$PAGE->set_totara_menu_selected(find_spaces::class);

$parameters = [
    'selected-source' => $source,
    'selected-sort' => $sort,
    'search-term' => $search_term
];

if (null !== $access && access::is_valid_code($access)) {
    // Check if the access value is valid.
    $parameters['selected-access'] = strtoupper($access);
}

$context = context_user::instance($USER->id);
if (!has_capability('container/workspace:workspacesview', $context, $USER->id)) {
    $PAGE->set_title(get_string('error:view_workspace', 'container_workspace'));
    $tui = new component('container_workspace/pages/WorkspaceEmptyPage');

} else {
    $tui = new component(
        'container_workspace/pages/SpacesPage',
        $parameters
    );
}

$tui->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->render($tui);
echo $OUTPUT->footer();