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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use mod_approval\plugininfo\approvalform;
use totara_core\advanced_feature;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

advanced_feature::require('approval_workflows');
require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('approvalformplugins');

$action = optional_param('action', null, PARAM_ALPHA);
$plugin = optional_param('plugin', null, PARAM_PLUGIN);

/** @var moodle_page $PAGE */
$url = new moodle_url('/mod/approval/form/manage_plugins.php');
$PAGE->set_url($url);

if ($action && $plugin) {
    require_sesskey();

    if ($action === 'enable') {
        approvalform::enable_plugin($plugin);
    } else if ($action === 'disable') {
        approvalform::disable_plugin($plugin);
    }
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('approvalform_plugins_list', 'mod_approval'));

/** @var mod_approval_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_approval');

// TODO: fancy vue component?
echo $renderer->render_approvalform_plugins();

echo $OUTPUT->footer();
