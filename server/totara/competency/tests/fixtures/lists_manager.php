<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

use totara_core\output\select_tree;


require(__DIR__ . '/../../../../config.php');

$displaydebugging = false;
if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    if (debugging()) {
        $displaydebugging = true;
    } else {
        throw new coding_exception('Invalid access detected.');
    }
}
$title = '\totara_competency\output\lists_manager testing page';

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);
$PAGE->set_context($context);
$PAGE->set_url('/totara/competency/tests/fixtures/lists_manager.php');
$PAGE->set_pagelayout('noblocks');
$PAGE->set_title($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if ($displaydebugging) {
    // This is intentionally hard coded - this page is not in the navigation and should only ever be used by developers.
    $msg = 'This page exists for acceptance testing, if you are here for another reason please file an improvement request.';
    echo $OUTPUT->notification($msg, 'notifysuccess');
    // We display a developer debug message as well to ensure that this doesn't not get shown during behat testing.
    debugging('This is a developer resource, please contact your system admin if you arrived here by mistake.', DEBUG_DEVELOPER);
}

$sorting = select_tree::create(
    'sorting',
    'order by',
    false,
    [
        (object)[
            'name' => 'order by column 1 ASC',
            'key' => 'column1_asc',
            'default' => true
        ],
        (object)[
            'name' => 'order by column 1 DESC',
            'key' => 'column1_desc',
        ]
    ],
    null,
    true,
    false,
    null,
    false
);

$selectable = optional_param('selectable', true, PARAM_BOOL);
$has_hierarchy = optional_param('has_hierarchy', true, PARAM_BOOL);
$has_paging = optional_param('has_paging', true, PARAM_BOOL);
$has_count = optional_param('has_count', true, PARAM_BOOL);
$has_order = optional_param('has_order', true, PARAM_BOOL);
$has_toggle = optional_param('has_toggle', true, PARAM_BOOL);

$data = [
    'has_level_toggle' => $has_toggle,
    'order_by' => $has_order ? $OUTPUT->render($sorting) : null,
    'has_paging' => $has_paging,
    'has_count' => $has_count,
    'has_hierarchy' => $has_hierarchy,
    'has_checkboxes' => $selectable,
];

echo $OUTPUT->render_from_template('totara_competency/test_lists_manager', $data);
echo $OUTPUT->footer();
