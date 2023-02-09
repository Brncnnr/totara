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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\interactor\workspace\interactor;
use container_workspace\query\file\sort;
use container_workspace\totara\menu\your_spaces;
use core_container\factory;
use totara_core\advanced_feature;
use totara_tui\output\component;
use container_workspace\workspace;

require_once(__DIR__ . "/../../../config.php");
global $OUTPUT, $PAGE;

require_login();
advanced_feature::require('container_workspace');

$workspace_id = required_param('id', PARAM_INT);

$sort = optional_param('source', sort::RECENT, PARAM_INT);
$extension = optional_param('source', '', PARAM_ALPHA);

/** @var workspace $workspace */
$workspace = factory::from_id($workspace_id);

if (!$workspace->is_typeof(workspace::get_type())) {
    throw new \coding_exception("Cannot view the files of non workspace container");
}

$interactor = new interactor($workspace);
$context = $workspace->get_context();

$PAGE->set_context($context);

if ($interactor->can_view_workspace()) {
    $PAGE->set_title(get_string('files', 'container_workspace'));
} else {
    $PAGE->set_title(get_string('error:view_workspace', 'container_workspace'));
}

$PAGE->set_pagelayout('legacynolayout');
$PAGE->set_url("/container/type/workspace/workspace_files.php", ['id' => $workspace_id]);
$PAGE->set_totara_menu_selected(your_spaces::class);

if (!sort::is_valid($sort)) {
    $sort = sort::RECENT;
}

$tui = new component('container_workspace/pages/WorkspaceEmptyPage');

if ($interactor->can_view_workspace()) {
    $tui = new component(
        'container_workspace/pages/WorkspaceFilePage',
        [
            'workspace-id' => $workspace_id,
            'workspace-name' => $workspace->fullname,
            'selected-sort' => sort::get_code($sort),
            'selected-extension' => $extension,
        ]
    );
}

$tui->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->render($tui);
echo $OUTPUT->footer();