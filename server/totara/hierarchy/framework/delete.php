<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../config.php');
require_once('../lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');


///
/// Setup / loading data
///

$sitecontext = context_system::instance();

// Get params
$prefix   = required_param('prefix', PARAM_SAFEDIR);
$id     = required_param('id', PARAM_INT);
// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);

hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

// Setup page and check permissions
admin_externalpage_setup($prefix.'manage','',array('prefix' => $prefix));

require_capability('totara/hierarchy:delete'.$prefix.'frameworks', $sitecontext);

$framework = $hierarchy->get_framework($id);

///
/// Display page
///
$PAGE->navbar->add(get_string("{$prefix}frameworks", 'totara_hierarchy'),
                    new moodle_url('/totara/hierarchy/framework/index.php', array('prefix' => $prefix)));
$PAGE->navbar->add(get_string('deleteframework', 'totara_hierarchy', format_string($framework->fullname)));

if (!$delete) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleteframework', 'totara_hierarchy', format_string($framework->fullname)), 2);
    echo $hierarchy->delete_framework_confirmation_modal($framework, ['sesskey' => $USER->sesskey], $OUTPUT);
    echo $OUTPUT->footer();
    exit;
}


///
/// Delete framework
///

if ($delete != md5($framework->timemodified)) {
    print_error('invalidcheck', 'totara_hierarchy');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

if ($hierarchy->delete_framework()) {
    $eventclass = "\\hierarchy_{$prefix}\\event\\framework_deleted";
    $eventclass::create_from_instance($framework)->trigger();

    \core\notification::success(get_string($prefix.'deletedframework', 'totara_hierarchy', $framework->fullname));
} else {
    \core\notification::error(get_string($prefix.'error:deletedframework', 'totara_hierarchy', $framework->fullname));
}
redirect(new moodle_url('/totara/hierarchy/framework/index.php', ['prefix' => $prefix]));
